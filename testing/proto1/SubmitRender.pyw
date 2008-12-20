#!/usr/bin/python
'''
AUTHOR: Oliver Palmer
CONTACT: opalme20@student.scad.edu || (703)725-6544
INITIAL: Dec 19 2008
PURPOSE: Program used to call up the render gui and submit renders
'''

import os
import sys
from subprocess import *
from PyQt4.QtCore import *
from PyQt4.QtGui import *
import lib.Ui_SubmitRender

__version__ = "0.0.1"
__author__ = "Oliver Palmer"

class SubmitRender(QWidget, lib.Ui_SubmitRender.Ui_SubmitRender):
    '''Class to import, run, and manipulate the main gui'''
    def __init__(self, parent=None):
        super(SubmitRender, self).__init__(parent)
        self.setupUi(self)
        self.startFrame()

def test(value):
    print value
app = QApplication(sys.argv)
form = SubmitRender()
form.connect(form, SIGNAL("valueChanged"), test)
form.show()
app.exec_()