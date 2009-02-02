#!/usr/bin/python
'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Jan 12 2009
PURPOSE: TCP client used to send information to the server and react to
signals sent from the server
'''
# From Python
import sys
import os.path
# From PyQt
from PyQt4.QtCore import *
from PyQt4.QtGui import *
from PyQt4.QtNetwork import *
# From PyFarm
## ui components
from lib.ui.RC1 import Ui_RC1
from lib.ui.CustomWidgets import *
## general libs
import lib.Que
from lib.Network import *
import lib.ReadSettings as Settings

# setup the required ports (adjust these settings via settings.cfg)
CFG = os.getcwd()+'/settings.cfg'
QUE_PORT = Settings.Network(CFG).QuePort()
BROADCAST_PORT = Settings.Network(CFG).BroadcastPort()
STDOUT_PORT = Settings.Network(CFG).StdOutPort()
STDERR_PORT = Settings.Network(CFG).StdErrPort()
SIZEOF_UINT16 = Settings.Network(CFG).Unit16Size()
SERVE_FROM = Settings.Network(CFG).MasterAddress()

class QueMaster(object):
    '''Master que class handles all communication with the que'''
    def __init__(self, parent=None):
        super(QueMaster, self).__init__(parent)
        self.que = lib.Que.TCPQue(self)
        self.que.listen(QHostAddress(SERVE_FROM), QUE_PORT)

    def put(self, command):
        '''Put an item into the que'''
        lib.Que.QUE.put(command)

    def get(self):
        '''Get an item from the que'''
        return lib.Que.QUE.get()

    def size(self):
        '''Return the size of the current que'''
        return lib.Que.QUE.qsize()

class RC1(QMainWindow):
    def __init__(self):
        super(RC1, self).__init__()

        # setup UI
        self.ui = Ui_RC1()
        self.ui.setupUi(self)

        # add external libs
        self.netTableLib = NetworkTable()
        self.que = lib.Que.QUE

        # inform the user of their settings
        self.updateStatus('SETTINGS', 'Got settings from ./settings.cfg', 'purple')
        self.updateStatus('SETTINGS', 'Server IP: %s' % SERVE_FROM, 'purple')
        self.updateStatus('SETTINGS', 'Broadcast Port: %s' % BROADCAST_PORT, 'purple')
        self.updateStatus('SETTINGS', 'StdOut Port: %s' % STDOUT_PORT, 'purple')
        self.updateStatus('SETTINGS', 'StdErr Port: %s' % STDERR_PORT, 'purple')
        self.updateStatus('SETTINGS', 'Que Port: %s' % QUE_PORT, 'purple')

        # setup ui vars
        self.hosts = []
        self.foundHosts = 0
        self.ui.networkTable.setAlternatingRowColors(True)
        self.software = self.ui.softwarePackages
        self.netTable = self.ui.networkTable
        self.netTable.horizontalHeader().setStretchLastSection(True)
        self.message = QString()
        self.scene = ''
        self.que = QueMaster()


        # setup socket vars
        self.socket = QTcpSocket()
        self.nextBlockSize = 0
        self.request = None

        # make signal connections
        ## ui signals
        self.connect(self.ui.render, SIGNAL("pressed()"), self._gatherInfo)
        self.connect(self.ui.findHosts, SIGNAL("pressed()"), self._findHosts)
        self.connect(self.ui.browseForScene, SIGNAL("pressed()"), self._browseForScene)

        ## socket signals
        self.connect(self.socket, SIGNAL("connected()"), self.sendRequest)
        self.connect(self.socket, SIGNAL("readyRead()"), self.readResponse)
        self.connect(self.socket, SIGNAL("disconnected()"), self.serverHasStopped)
        self.connect(self.socket, SIGNAL("error(QAbstractSocket::SocketError)"), self.serverHasError)

        ## network section signals
        self.connect(self.ui.disableHost, SIGNAL("pressed()"), self._disableHosts)
        self.connect(self.ui.addHost, SIGNAL("pressed()"), self._customHostDialog)
        self.connect(self.ui.removeHost, SIGNAL("pressed()"), self._removeSelectedHost)


    def _browseForScene(self):
        '''Browse for a scene to render'''
        getScene = QFileDialog.getOpenFileName(\
            None,
            self.trUtf8("Select File"),
            QString(),
            self.trUtf8("All Files(*.*);;Maya (*.mb *.ma);;Houdini (*.hip *.ifd);;Shake(*.shk)"),
            None,
            QFileDialog.Options(QFileDialog.DontResolveSymlinks))
        if not getScene.isEmpty():
            self.ui.inputScene.setText(getScene)

    def _removeSelectedHost(self):
        '''Remove the currently selected host'''
        lineHost = self._getHostSelection()
        self.hosts.remove(lineHost[1])
        self.netTable.removeRow(lineHost[0])

    def _getHostSelection(self):
        '''
        Get the current host selection

        OUTPUT:
            list2 - [rowNum, [hostname, ipaddress, status]]
        '''
        output = []
        tmp = []
        output.append(list(self.netTable.selectedIndexes())[0].row())
        for i in list(self.netTable.selectedItems()):
            tmp.append(str(i.text()))

        output.append(tmp)

        return output

    def _customHostDialog(self):
        ''''Open a dialog to add a custom host'''
        self.customHostDialog = AddHostDialog()
        self.connect(self.customHostDialog.buttons, SIGNAL("accepted()"), self._addCustomHost)
        self.customHostDialog.exec_()

    def _addCustomHost(self):
        '''Add the custom host to self.hosts and the gui, close the dialog'''
        host = self.customHostDialog.inputHost.text()
        self.addHost(str(host))
        self.customHostDialog.close()

    def addHost(self, host, warnHostExists=True):
        '''
        Add the given host to the table

        INPUT:
            host (string) - host to add
            warnHostExists (bool) - if false, do not popup info about
            hosts that have already been added
        '''
        # check to make sure the host is valid
        if ResolveHost(host) == 'BAD_HOST':
            self.criticalMessage("Bad host or IP", "Sorry %s could not be resolved, please check your entry and try again." % host)
        else:
            # prepare the information
            self.currentHost = []
            self.hostStatusMenu = HostStatus(self)
            self.currentHost.append(ResolveHost(host)[0])
            self.currentHost.append(ResolveHost(host)[1])
            self.currentHost.append('Online')

            # if the current host has not been added
            if self.currentHost not in self.hosts:
                self.hosts.append(self.currentHost)
                self.addHostToTable(self.currentHost)
                self.foundHosts += 1
            else:
                if warnHostExists:
                    msg = QMessageBox()
                    msg.information(None, "Host Already Added", unicode("%s has already been added to the list." % host))
                else:
                    pass

    def addHostFromBroadcast(self, host):
        '''Add a host generated from the broadcast packet'''
        self.addHost(host, False)

    def addHostToTable(self, resolvedHost):
        '''Add the given host to the table'''
        y = 0
        x = self.ui.networkTable.rowCount()
        self.ui.networkTable.insertRow(self.ui.networkTable.rowCount())

        for attribute in resolvedHost:
            item = QTableWidgetItem(attribute)
            self.ui.networkTable.setItem(x, y, item)
            y += 1

#        a = QTableWidgetItem()
#        b = HostStatusMenu(self)
#        a.setData(0, QVariant(b.resize(300, 300)))
#        b.show()
#        self.ui.networkTable.setItem(x, 2, a)

        #self.netTable.resizeColumnsToContents()

    def _disableHosts(self):
        row = self._getHostSelection()[0]
        item = QTableWidgetItem('Offline')
        self.netTable.setItem(2, row, item)

    def _isExt(self, inFile, trueExtension):
        '''
        Returns true if the extension of the input

        VARS:
            inFile -- The file that comes from inputScene
            trueExtension --  The extesion that you are expecting
        '''
        if os.path.splitext(str(inFile))[1].split('.')[1] == trueExtension:
            return True
        else:
            return False

    def criticalMessage(self, title, message):
        '''
        Pop up critical message window

        VARS:
            title -- Title of window
            message -- message to display
        '''
        msg = QMessageBox()
        msg.critical(None, title, unicode(message))

    def _gatherInfo(self):
        '''Gather information about the current job'''
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
                self.rayFlag = '-r mr'
            else:
                self.rayFlag = ''

            # get information from the drop down menu
            if self.software.currentText() == 'Maya 2008':
                # make sure that we are looking at maya extensions
                #if not self._isExt(self.scene, 'ma') or self._isExt(self.scene, 'mb'):
                    #self.criticalMessage("Bad Input File", "You are rendering with Maya please select a Maya scene.")
                #else:
                self.command = '/usr/autodesk/maya2008-x64/bin/Render'

            elif self.software.currentText() == 'Maya 2009':
                #if not self._isExt(self.scene, 'ma') or self._isExt(self.scene, 'mb'):
                    #self.criticalMessage("Bad Input File", "You are rendering with Maya please select a Maya scene.")
                #else:
                self.command = '/usr/autodesk/maya2009-x64/bin/Render'

            elif self.software.currentText() == 'Shake':
                self.command = '/opt/shake/bin/shake'

            for frame in range(int(self.sFrame),int(self.eFrame)+1, int(self.bFrame)):
                if self.rayFlag == '':
                    self.que.put('%s -s %s -e %s -v 5 %s' % (self.command, frame, frame, self.scene))
                else:
                    self.que.put('%s %s -s %s -e %s -v 5 %s' % (self.command, self.rayFlag, frame, frame, self.scene))

            self.updateStatus('JOB', '%s waiting in Que' % self.que.size(), 'blue')

    def _findHosts(self):
        '''Get hosts via broadcast packet, add them to self.hosts'''
        self.updateStatus('BroadcastClient (gui)', 'Searching for hosts...', 'green')
        findHosts = BroadcastServer(self)
        self.connect(findHosts, SIGNAL("gotNode"), self.addHostFromBroadcast)
        self.connect(findHosts,  SIGNAL("DONE"),  self._doneFindingHosts)
        findHosts.start()

    def _doneFindingHosts(self):
        '''Functions to run when done finding hosts'''
        # inform the user of the number of hosts found
        self.updateStatus('BroadcastClient (gui)', 'Found %i new hosts, search complete.' % self.foundHosts, 'green')
        self.foundHosts = 0

    def updateStatus(self, section, msg, color='black'):
        '''
        Update the ui's status window

        VARS:
            section (string)-- The section to report from (ex. NETWORK)
            msg (string) - The message to post
            color (string) - The color name or hex value to set the section
        '''
        self.ui.status.append('<font color=%s><b>%s</b></font> - %s' % (color, section, msg))

    def issueRequest(self, action, job, frame):
        '''Pack the data and ready it to be sent'''
        self.request = QByteArray()
        stream = QDataStream(self.request, QIODevice.WriteOnly)
        stream.setVersion(QDataStream.Qt_4_2)
        stream.writeUInt16(0)
        stream << action << job << frame
        stream.device().seek(0)
        stream.writeUInt16(self.request.size() - SIZEOF_UINT16)
        if self.socket.isOpen():
            self.socket.close()
        self.updateStatus('TCPClient (gui)', 'Packing request', 'green')

        # once the socket emits connected() self.sendRequest is called
        self.socket.connectToHost("localhost", STDOUT_PORT)

    def sendRequest(self):
        '''Send the requested data to the remote server'''
        self.updateStatus('TCPClient (gui)', 'Sending request', 'green')
        self.nextBlockSize = 0
        self.socket.write(self.request)
        self.request = None

    def readResponse(self):
        '''Read the response from the server'''
        self.updateStatus('TCPServer', 'Successful connection', 'green')
        self.updateStatus('TCPClient (gui)', 'Reading response', 'green')
        stream = QDataStream(self.socket)
        stream.setVersion(QDataStream.Qt_4_2)

        while True:
            if self.nextBlockSize == 0:
                if self.socket.bytesAvailable() < SIZEOF_UINT16:
                    break
                self.nextBlockSize = stream.readUInt16()
            if self.socket.bytesAvailable() < self.nextBlockSize:
                break

            action = QString()
            job = QString()
            frame = QString()

            stream >> action >> job >> frame
            if action == "ERROR":
                msg = QString("Error: %1").arg(command)
            elif action == "RENDER":
                msg = QString("Rendering frame %2 of job %1").arg(job).arg(frame)
                self.updateStatus('TCPServer', msg, 'green')
            self.nextBlockSize = 0

    def serverHasStopped(self):
        '''Run upon server shutdown'''
        self.updateStatus('TCPClient (gui)', '<font color=red><b>Server Thread Killed</b></font>','green')
        self.socket.close()

    def serverHasError(self, error):
        '''Gather errors then close the connection'''
        self.updateStatus('TCPClient (gui)', QString("<font color='red'><b>Error: %1</b></font>").arg(self.socket.errorString()), 'green')
        self.socket.close()


app = QApplication(sys.argv)
ui = RC1()
ui.show()
app.exec_()
