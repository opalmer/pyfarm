'''
HOMEPAGE: www.pyfarm.net
INITIAL: Jan 24 2009
PURPOSE: Module full of 'general widgets', widgets used inside of other widgets.

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
from sys import argv, exit

# From PyQt
#  main widgets, sub widgets, and utilities
from PyQt4.QtGui import QWidget, QComboBox, QDialog, QDialogButtonBox, QApplication, QProgressDialog
from PyQt4.QtGui import QMenu, QLabel, QMessageBox, QLineEdit
from PyQt4.QtGui import QFont, QVBoxLayout, QGridLayout
from PyQt4.QtCore import SIGNAL, SLOT, QString, QObject

# From PyFarm
from lib.Logger import Logger
from lib.ui.HostInfo import Ui_HostInfo

__MODULE__ = "lib.ui.main.CustomWidgets"

class HostStatus(QComboBox):
    '''Combo box used to enable/disable hosts'''
    def __init__(self, parent=None):
        super(HostStatus, self).__init__(parent)
        self.options = ['Enabled', 'Disabled']

    def addOptions(self):
        for option in self.options:
            self.append(option)

class AddHostDialog(QDialog):
    def __init__(self, parent=None):
        super(AddHostDialog, self).__init__(parent)
        self.setupUi(self)

    def setupUi(self, AddCustomHost):
        AddCustomHost.setObjectName("AddCustomHost")
        AddCustomHost.resize(329, 113)
        self.widget = QWidget(AddCustomHost)
        self.widget.setGeometry(QRect(20, 24, 281, 70))
        self.widget.setObjectName("widget")
        self.verticalLayout = QVBoxLayout(self.widget)
        self.verticalLayout.setObjectName("verticalLayout")
        self.horizontalLayout = QHBoxLayout()
        self.horizontalLayout.setObjectName("horizontalLayout")
        self.hostLabel = QLabel(self.widget)
        font = QFont()
        font.setPointSize(10)
        self.hostLabel.setFont(font)
        self.hostLabel.setObjectName("hostLabel")
        self.horizontalLayout.addWidget(self.hostLabel)
        self.inputHost = QLineEdit(self.widget)
        self.inputHost.setObjectName("inputHost")
        self.horizontalLayout.addWidget(self.inputHost)
        self.verticalLayout.addLayout(self.horizontalLayout)
        self.buttons = QDialogButtonBox(self.widget)
        self.buttons.setStandardButtons(QDialogButtonBox.Cancel|QDialogButtonBox.Ok)
        self.buttons.setObjectName("buttons")
        self.verticalLayout.addWidget(self.buttons)

        self.retranslateUi(AddCustomHost)
        QObject.connect(self.buttons, SIGNAL("rejected()"), AddCustomHost.close)
        QMetaObject.connectSlotsByName(AddCustomHost)


    def retranslateUi(self, AddCustomHost):
        AddCustomHost.setWindowTitle(QApplication.translate("AddCustomHost", "Add Custom Host", None, QApplication.UnicodeUTF8))
        self.hostLabel.setText(QApplication.translate("AddCustomHost", "Host:", None, QApplication.UnicodeUTF8))


class HostStatusMenu(QWidget):
    def __init__(self, parent=None):
        super(HostStatusMenu, self).__init__(parent)
        self.options = ['Online', 'Offline']
        self.menu = QComboBox()
        self.addOptions()

    def addOptions(self):
        for option in self.options:
            self.menu.addItem(option)


class NetworkTable(object):
    def __init__(self, parent=None):
        super(NetworkTable, self).__init__(parent)
        self.modName = 'CustomWidgets.NetworkTable'

    def contextMenuEvent(self, event):
        menu = QMenu(self)
        oneAction = menu.addAction("&One")
        twoAction = menu.addAction("&Two")
        self.connect(oneAction, SIGNAL("triggered()"), self.one)
        self.connect(twoAction, SIGNAL("triggered()"), self.two)
        menu.exec_(event.globalPos())

    def one(self):
        self.message = QString("Menu option One")
        log("PyFarm :: %s :: This is the first menu option" % self.modName, 'debug')
        print
        #self.update()

    def two(self):
        self.message = QString("Menu option Two")
        log("PyFarm :: %s :: This is the second menu option" % self.modName, 'debug')

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
                self._addHostToTable(self.currentHost)
                self.foundHosts += 1
            else:
                if warnHostExists:
                    msg = QMessageBox()
                    msg.information(None, "Host Already Added", unicode("%s has already been added to the list." % host))
                else:
                    pass

    def _addHostToTable(self, resolvedHost):
            '''Add the given host to the table'''
            y = 0
            x = self.ui.networkTable.rowCount()
            self.ui.networkTable.insertRow(self.ui.networkTable.rowCount())

            for attribute in resolvedHost:
                item = QTableWidgetItem(attribute)
                self.ui.networkTable.setItem(x, y, item)
                y += 1

class DialogBox(QDialog):
    def __init__(self, parent=None):
        super(DialogBox, self).__init__(parent)

    def setupUi(self, parent=None):
        super(Ui_DialogBox).__init__(QWidget)
        parent.setObjectName("parent")
        parent.resize(354, 118)
        self.widget = QWidget(parent)
        self.widget.setGeometry(QRect(11, 13, 332, 96))
        self.widget.setObjectName("widget")
        self.verticalLayout = QVBoxLayout(self.widget)
        self.verticalLayout.setObjectName("verticalLayout")
        self.horizontalLayout_2 = QHBoxLayout()
        self.horizontalLayout_2.setObjectName("horizontalLayout_2")
        self.label = QLabel(self.widget)
        self.label.setObjectName("label")
        self.horizontalLayout_2.addWidget(self.label)
        spacerItem = QSpacerItem(248, 20, QSizePolicy.Expanding, QSizePolicy.Minimum)
        self.horizontalLayout_2.addItem(spacerItem)
        self.verticalLayout.addLayout(self.horizontalLayout_2)
        self.customObjectName = QLineEdit(self.widget)
        self.customObjectName.setObjectName("customObjectName")
        self.verticalLayout.addWidget(self.customObjectName)
        self.buttonBox = QDialogButtonBox(self.widget)
        self.buttonBox.setOrientation(Qt.Horizontal)
        self.buttonBox.setStandardButtons(QDialogButtonBox.Cancel|QDialogButtonBox.Ok)
        self.buttonBox.setObjectName("buttonBox")
        self.verticalLayout.addWidget(self.buttonBox)
        self.retranslateUi(parent)
        QObject.connect(self.buttonBox, SIGNAL("rejected()"), parent.close)
        QMetaObject.connectSlotsByName(parent)
        parent.exec_()

    def retranslateUi(self, parent):
        parent.setWindowTitle(QApplication.translate("parent", "Add Custom Object", None, QApplication.UnicodeUTF8))
        self.label.setText(QApplication.translate("parent", "Object Name:", None, QApplication.UnicodeUTF8))


class CustomObjectDialog(QDialog):
    def __init__(self, parent=None):
        super(CustomObjectDialog, self).__init__(parent)
        lineEditLabel = QLabel("Object Name:")
        self.objectEditName = QLineEdit()
        buttonBox = QDialogButtonBox(QDialogButtonBox.Ok|QDialogButtonBox.Cancel)
        grid = QGridLayout()
        grid.addWidget(lineEditLabel, 0, 0)
        grid.addWidget(self.objectEditName, 1, 0)
        grid.addWidget(buttonBox, 4, 0, 1, 2)
        self.setLayout(grid)
        self.connect(buttonBox, SIGNAL("accepted()"), self, SLOT("accept()"))
        self.connect(buttonBox, SIGNAL("rejected()"), self, SLOT("reject()"))
        self.setWindowTitle("Add Custom Object")

    def accept(self):
        self.emit(SIGNAL("objectName"), self.objectEditName.text())
        QDialog.accept(self)

class HostInfo(QDialog):
    def __init__(self, parent=None):
        super(HostInfo, self).__init__(parent)
        self.ui = Ui_HostInfo()
        self.ui.setupUi(self)


class XMLFileNotFound(QObject):
    '''
    Given an xml file string, tell the user that the
    file could not be found
    '''
    def __init__(self, doc, type, parent=None):
        super(XMLFileNotFound, self).__init__(parent)
        self.doc = doc
        if type == 'gui':
            app = QApplication(argv)
            exit(self.showGuiMsg())
            app.exec_()
        else:
            self.showCommandLineMsg()

    def showCommandLineMsg(self):
        '''If given a command line interface, show this message'''
        exit("\n[ FATAL ERROR ]\n\tThe XML file %s could not be found."\
        "\n\tPlease be sure that the file exists and is readable.\n[ FATAL ERROR ]\n" % self.doc)

    def showGuiMsg(self):
        '''If given a gui interface, show a message box'''
        parent = QWidget()
        msg = QMessageBox()
        title = QString("XML File Error -- Could not load XML")
        message = QString("The XML file %s could not be found."\
        "\tPlease be sure that the file exists and is readable." % self.doc)
        msg.critical(parent, title, message)


class ProgressDialog(QProgressDialog):
    '''
    Used to give the user information about the
    current progress of a network broadcast.
    '''
    def __init__(self, labelText, cancelButtonText, minimum, maximum, parent=None):
        super(ProgressDialog, self).__init__(parent)
        self.setMinimumSize(350, 100)
        self.setLabelText(labelText)
        self.setCancelButtonText(cancelButtonText)
        self.setRange(minimum, maximum)

    def next(self):
        '''Incriment the progress'''
        value = self.value()+1
        self.setValue(value)


class MessageBox(object):
    '''Designed to create a QmessageBox with configurable inputs'''
    def __init__(self, parent):
        self.msg = QMessageBox()
        self.parent = parent

    def critical(self, title, message, code):
        '''
        Pop up critical message window

        VARS:
            title (str) -- Title of window
            message (str) -- message to display
            code (int) -- exit code to give to sys.exit
        '''
        self.msg.critical(self.parent, title, unicode(message))
        exit(code)

    def warning(self, title, message):
        '''
        Pop up critical message window

        VARS:
            title -- Title of window
            message -- message to display
        '''
        self.msg.warning(self.parent, title, unicode(message))

    def info(self, title, message):
        '''
        Pop up critical message window

        VARS:
            title -- Title of window
            message -- message to display
        '''
        self.msg.information(self.parent, title, unicode(message))