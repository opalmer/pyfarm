'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 7 2008 (revised in July 2010)
PURPOSE: Group of classes dedicated to the collection and monitoring
of queue information.

This file is part of PyFarm.
Copyright (C) 2008-2011 Oliver Palmer

PyFarm is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

PyFarm is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''
import os
import sys

from PyQt4 import QtCore, QtNetwork

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, "..", "..", ".."))
MODULE = os.path.basename(__file__)
if PYFARM not in sys.path: sys.path.append(PYFARM)

import xmlrpc
from lib import logger, system, net

UNIT16         = 8
STREAM_VERSION = net.dataStream()
logger         = logger.Logger()
types          = []

class RPCTestThread(xmlrpc.BaseServerThread):
    '''
    Test server thread
    '''
    def tupleResult(self):
        return True, True

    def time(self, t):
        print t
        return t

    def add(self, a, b, c=1, d=1, e=1):
        return a + b + c + d + e

    def response(self):
        return True

    def stop(self):
        sys.exit()
        return True

if __name__ == '__main__':
    logger.info("Starting: %i" % os.getpid())
    app    = QtCore.QCoreApplication(sys.argv)
    server = xmlrpc.BaseServer(threadClass=RPCTestThread)

    if server.listen(QtNetwork.QHostAddress("127.0.0.1"), 54000):
        logger.netserver("RPC Server Running on port 54000")

    sys.exit(app.exec_())