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
# From PyQt
from PyQt4.QtCore import *
from PyQt4.QtGui import *
from PyQt4.QtNetwork import *
# From PyFarm
## ui components
from lib.ui.RC1 import Ui_RC1
from lib.ui.CustomWidgets import *
#from lib.ui.AddCustomHost import AddHostDialog
## general libs
from lib.Util import *
from lib.Network import *

PORT = 9407
SIZEOF_UINT16 = 2

class RC1(QMainWindow):
    def __init__(self):
        super(RC1, self).__init__()

        # setup UI
        self.ui = Ui_RC1()
        self.ui.setupUi(self)

        # add external libs
        self.netTableLib = NetworkTable()

        # setup ui vars
        self.hosts = []
        self.foundHosts = 0
        self.ui.networkTable.setAlternatingRowColors(True)
        self.netTable = self.ui.networkTable
        self.netTable.horizontalHeader().setStretchLastSection(True)
        self.message = QString()

        # setup socket vars
        self.socket = QTcpSocket()
        self.nextBlockSize = 0
        self.request = None

        # make signal connections
        ## ui signals
        self.connect(self.ui.render, SIGNAL("pressed()"), self._gatherInfo)
        self.connect(self.ui.findHosts, SIGNAL("pressed()"), self._findHosts)

        ## socket signals
        self.connect(self.socket, SIGNAL("connected()"), self.sendRequest)
        self.connect(self.socket, SIGNAL("readyRead()"), self.readResponse)
        self.connect(self.socket, SIGNAL("disconnected()"), self.serverHasStopped)
        self.connect(self.socket, SIGNAL("error(QAbstractSocket::SocketError)"), self.serverHasError)

        ## network section signals
        self.connect(self.ui.disableHost, SIGNAL("pressed()"), self._disableHosts)
        self.connect(self.ui.addHost, SIGNAL("pressed()"), self._customHostDialog)
        self.connect(self.ui.removeHost, SIGNAL("pressed()"), self._removeSelectedHost)

        # run some programs
        '''
        // create table, let it be as follows
        QTableWidget *table = new QTableWidget;
        connect(table, SIGNAL(customContextMenuRequested ( const QPoint &)), this, SLOT(popupYourMenu(const QPoint &)));

        void YourClass:popupYourMenu(const QPoint & pos)
        {
        // create popupMenu as QMenu object
        // populate it

        popupMenu->popup(pos);
        }
        '''
    def contextMenuEvent(self, event):
        menu = QMenu(self)
        oneAction = menu.addAction("&One")
        twoAction = menu.addAction("&Two")
        self.connect(oneAction, SIGNAL("triggered()"), self.one)
        self.connect(twoAction, SIGNAL("triggered()"), self.two)
        menu.exec_(event.globalPos())

    def one(self):
        self.message = QString("Menu option One")
        print "Menu option One"
        #self.update()

    def two(self):
        self.message = QString("Menu option Two")
        print "Menu option Two"
        #self.update()

#    def three(self):
#        self.message = QString("Menu option Three")
#        print "Menu option Three"
#        self.update()

#    def event(self, event):
#        if event.type() == QEvent.KeyPress and \
#           event.key() == Qt.Key_Tab:
#            self.key = QString("Tab captured in event()")
#            print "Captured tab"
#            self.update()
#            return True
#        return QWidget.event(self, event)

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
            msg = QMessageBox()
            msg.critical(None, "Bad host or IP", unicode("Sorry %s could not be resolved, please check your entry and try again." % host))
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
        print "Disable"
        row = self._getHostSelection()[0]
        item = QTableWidgetItem('Offline')
        self.netTable.setItem(2, row, item)

    def _gatherInfo(self):
        '''Gather information about the current job'''
        self.job = self.ui.inputJobName.text()
        self.sFrame = self.ui.inputStartFrame.text()
        self.eFrame = self.ui.inputEndFrame.text()
        self.issueRequest(QString("RENDER"), self.job, self.sFrame)

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
        self.socket.connectToHost("localhost", PORT)

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
