#!/usr/bin/python
'''
AUTHOR: Oliver Palmer
CONTACT: oliverpalmer@opalmer.com || (703)725-6544
INITIAL: Dec 19 2008
PURPOSE: Classes used to call up the render gui and
submit renders
'''

import sys
import time
from PyQt4.QtGui import *
from PyQt4.QtCore import *
from lib.Process import *
from lib.Network import *
from lib.FarmLog import *
from lib.ui.Proto1_5 import Ui_Proto1_5


__VERSION__ = '0.0.60'
__AUTHOR__ = 'Oliver Palmer'
__CONTACT__ = 'opalme20@student.scad.edu'
__DESCRIPTION__ = 'PyFarm is a distributed \
rendering package that can be used in everyday \
production.  Distributed under the General Public \
License version 3.'

log = FarmLog("Main.pyw")
log.setLevel('info')
        
class Proto1_5(QDialog):
    '''
    Prototype class implimenting the Qt Designer generated user
    interface.

    REQUIRES:
        PyQt:
            QDialog

        Python:
            sys
            time

        PyFarm:
            lib.ui.ui_Proto1
            lib.FarmLog

    INPUT:
        None
    '''
    def __init__(self):
        # first setup the user interface from QtDesigner
        super(Proto1_5, self).__init__()
        self.ui = Ui_Proto1_5()
        self.ui.setupUi(self)

        # initilize some required variables
        self.scene = None
        self.sFrame = None
        self.eFrame = None
        self.hosts = []
        self.hostNum = 0
        self.newHosts = 0

        # Connect Qt signals to actions
        self.connect(self.ui.renderButton, SIGNAL("pressed()"), self._startRender)
        self.connect(self.ui.aboutButton,  SIGNAL("pressed()"),  self._about)
        self.connect(self.ui.findNodesButton,  SIGNAL("pressed()"),  self._getHosts)

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
        message = 'AUTHOR: %s \
               \nCONTACT: %s \
               \nVERSION: %s \
               \n\n%s' % \
        (__AUTHOR__, __CONTACT__,
        __VERSION__, __DESCRIPTION__)

        about = QMessageBox()
        about.information(None, "PyFarm -- Prototype 1 - About", message)
            
    def _doneFindingHosts(self):
        # inform the user of new hosts
        self.ui.console.append('<font color=blue>NETWORK</font> - Found %s new hosts' % self.newHosts)
        self.newHosts = 0
        
        
    def _addHost(self, host):
        '''Given an input host, add it to the host list'''
        # if it has alredy been added, don't add it
        if host not in self.hosts:
            self.hosts.append(host)
            self.ui.hostList.addItem(host)
            self.newHosts += 1
        
    def _getHosts(self):
        '''Get hosts via mulicast packet, add them to self.hosts'''
        self.ui.console.append('<font color=blue>NETWORK</font> - Searching for hosts...')
        findHosts = MulticastServer()
        self.connect(findHosts, SIGNAL("gotNode"), self._addHost)
        self.connect(findHosts,  SIGNAL("DONE"),  self._doneFindingHosts)
        findHosts.start()

    def _incrimentProgress(self, value):
        '''Run a test on the progress bar'''
        self.ui.progressBar.setValue(value)
            
            
    def _unthreadedProgress(self, start, end):
        '''An unthreaded progress bar'''
        for i in range(start, end+1):
            time.sleep(.2)
            self.ui.progressBar.setValue(i)
            
    def _startRender(self):
            '''Once the render button is pressed this function is
            executed starting the entire render process'''
            
            ### CHECK OUT THE PROCESS MODULE.PB it seems to be working 
            ####SLOWLY change the code to act as the progress bar stuff!
            #b = QThread()
            #b.start() 
            
            # lock in the variables for rendering
            self.scene = self._scene()
            self.sFrame = self._sFrame()
            self.eFrame = self._eFrame()

            # inform the user of their choices
            print "Starting Render"
            print "Scene: %s" % self.scene
            print "Start Frame: %s" % self.sFrame
            print "End Frame: %s" % self.eFrame
            
            # setup a test run for the progress bar
            self.ui.progressBar.setMinimum(self.sFrame)
            self.ui.progressBar.setMaximum(self.eFrame)
            self._unthreadedProgress(self.sFrame,  self.eFrame)


if __name__ == "__main__":
    app = QApplication(sys.argv)
    #window = QDialog()
    ui = Proto1_5()
    ui.show()
    sys.exit(app.exec_())
