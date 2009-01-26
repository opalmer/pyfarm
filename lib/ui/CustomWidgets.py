'''
AUTHOR: Oliver Palmer
CONTACT: oliverpalmer@opalmer.com || (703)725-6544
INITIAL: Jan 24 2009
PURPOSE: Module full of 'general widgets', widgets used inside of other widgets.
'''

from PyQt4.QtGui import *
from PyQt4.QtCore import *
from RC1 import *

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

    def contextMenuEvent(self, event):
        menu = QMenu(self)
        oneAction = menu.addAction("&One")
        twoAction = menu.addAction("&Two")
        self.connect(oneAction, SIGNAL("triggered()"), self.one)
        self.connect(twoAction, SIGNAL("triggered()"), self.two)
        #        if not self.message:
        #            menu.addSeparator()
        #            threeAction = menu.addAction("Thre&e")
        #            self.connect(threeAction, SIGNAL("triggered()"),self.three)
        menu.exec_(event.globalPos())

    def one(self):
        self.message = QString("Menu option One")
        print "Menu option One"
        #self.update()

    def two(self):
        self.message = QString("Menu option Two")
        print "Menu option Two"

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

    #        a = QTableWidgetItem()
    #        b = HostStatusMenu(self)
    #        a.setData(0, QVariant(b.resize(300, 300)))
    #        b.show()
    #        self.ui.networkTable.setItem(x, 2, a)

            #self.netTable.resizeColumnsToContents()
