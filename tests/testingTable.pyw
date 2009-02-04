#!/usr/bin/python

import sys
from PyQt4.QtGui import *
from lib.ui.testing import Ui_MainWindow


class TableTest(QDialog):
    '''
    Prototype class implimenting the Qt Designer generated user
    interface.

    REQUIRES:
        Python:
            sys
            time
        PyQt:
            QDialog
        PyFarm:
            lib.ui.ui_Proto1
            lib.FarmLog

    INPUT:
        None
    '''
    def __init__(self):
        # first setup the user interface from QtDesigner
        super(TableTest, self).__init__()
        self.ui = Ui_MainWindow()
        self.ui.setupUi(self)


if __name__ == "__main__":
    app = QApplication(sys.argv)
    ui = TableTest()
    ui.show()
    app.exec_()
