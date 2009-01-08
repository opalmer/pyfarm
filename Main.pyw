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


__VERSION__ = '0.0.74'
__AUTHOR__ = 'Oliver Palmer'
__CONTACT__ = 'opalme20@student.scad.edu'
__DESCRIPTION__ = 'PyFarm is a distributed \
rendering package that can be used in everyday \
production.  Distributed under the General Public \
License version 3.'

log = FarmLog("Main.pyw")
log.setLevel('debug')

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
        self.sFrame = self.ui.sFrameBox.value() # get the default value for sFrame
        self.eFrame = self.ui.eFrameBox.value() # get the default value for eFrame
        self.hosts = []
        self.hostNum = 0
        self.newHosts = 0
        self.rendering = False

        # Connect Qt signals to actions
        self.connect(self.ui.renderButton, SIGNAL("pressed()"), self._startRender)
        self.connect(self.ui.stopRender,  SIGNAL("pressed()"), self._stopRender)
        self.connect(self.ui.aboutButton,  SIGNAL("pressed()"),  self._about)
        self.connect(self.ui.findNodesButton,  SIGNAL("pressed()"),  self._getHosts)
        self.connect(self.ui.sFrameBox,  SIGNAL("valueChanged(int)"),  self._setStartFrame)
        self.connect(self.ui.eFrameBox,  SIGNAL("valueChanged(int)"),  self._setEndFrame)
        self.connect(self.ui.sceneEntry,  SIGNAL("textChanged(QString)"),  self._setScene)

        self._renderButtonSwap()

    def _setScene(self,  value):
        self.scene = str(value)
        log.debug('Changed start frame to: %s' % self.scene)

    def _setStartFrame(self,  value):
        self.sFrame = value
        self.ui.progressBar.setMinimum(value)
        log.debug('Changed start frame to: %i' % self.sFrame)

    def _setEndFrame(self, value):
        self.eFrame = value
        self.ui.progressBar.setMaximum(value)
        log.debug('Changed end frame to: %i' % self.eFrame)

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
            self.newHosts += 1 # count the number of NEW hosts

    def _getHosts(self):
        '''Get hosts via mulicast packet, add them to self.hosts'''
        self.ui.console.append('<font color=blue>NETWORK</font> - Searching for hosts...')
        findHosts = MulticastServer(self)
        self.connect(findHosts, SIGNAL("gotNode"), self._addHost)
        self.connect(findHosts,  SIGNAL("DONE"),  self._doneFindingHosts)
        findHosts.start()

    def _render(self):
        '''Run this function to start the render'''
        self.rendering = True
        self.ui.console.append('<font color=green>INFO</font> - Rendering')
        self.ui.progressBar.setValue(0)# reset the progress bar to 0
        self._renderButtonSwap()

        # setup the progress bar thread
        self.progress = ProtoRender(self, self.sFrame, self.eFrame)
        self.connect(self.progress, SIGNAL('frameComplete'), self.ui.progressBar.setValue)
        self.connect(self.progress, SIGNAL('progress_done'), self._renderComplete)
        self.progress.start()

    def _test(self):
        self.progress.exit()

    def _renderButtonSwap(self):
        '''Swap the current render button state'''
        if self.rendering:
            self.ui.renderButton.setDisabled(1)
            self.ui.stopRender.setDisabled(0)
        else:
            self.ui.renderButton.setDisabled(0)
            self.ui.stopRender.setDisabled(1)

    def _renderComplete(self):
        self.rendering = False
        self._renderButtonSwap()
        self.ui.console.append('<font color=green>INFO</font> - Rendering Complete')

    def _stopRender(self):
        self.progress.stop()
        self.rendering = False
        self._renderButtonSwap()
        self.ui.console.append('<font color=red>WARNING</font> - <b>RENDER TERMINATED</b>')

    def _startRender(self):
            '''Once the render button is pressed this function is
            executed starting the entire render process'''
            # if scene is not given error out

            if self.scene == None or self.scene == '':
                self.ui.console.append('<font color=orange>ERROR</font> - You must specify a scene to render')
            else:
                # inform the user of their choices
                self.ui.console.append('<font color=green>INFO</font> - Starting Render')
                log.debug("Scene: %s" % self.scene)
                log.debug("Start Frame: %s" % self.sFrame)
                log.debug("End Frame: %s" % self.eFrame)
                self._render()


if __name__ == "__main__":
    app = QApplication(sys.argv)
    ui = Proto1_5()
    ui.show()
    app.exec_()
