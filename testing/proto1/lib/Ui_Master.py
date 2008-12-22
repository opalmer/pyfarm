'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Dec 19 2008
PURPOSE: Program used to call up the render gui and submit renders
'''
import sys
from PyQt4.QtGui import *
from PyQt4.QtCore import *

class Proto1( QWidget ):
    '''First GUI generated to test the initial functions of PyFarm'''
    def __init__(self, parent=None):
        super(Proto1, self).__init__(parent)
        self.scene = QLineEdit()
        self.sFrame = QSpinBox()
        self.eFrame = QSpinBox()
        self.renderer = QComboBox()
        self.renderers = ['Mental Ray','Software','Vector']
        self.render.addItems( self.renderers )
        self.nodeList = QTableWidget()
        self.findNodes = QPushButton()
        self.render = QPushButton()
        self.quit = QPushButton()
        self.output = QTextBrowser()
        self.progress = QProgressBar()
        grid = QGridLayout()
        grid.addWidget( self.renderer, 0, 0 )
        self.setLayout( grid )


app = QApplication(sys.argv)
form = Proto1()
form.show()
app.exec_()
