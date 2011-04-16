'''
HOMEPAGE: www.pyfarm.net
INITIAL: April 10 2011
PURPOSE: To provide a PyQt compatible xmlrpc server

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
import cPickle
import xmlrpclib

from PyQt4 import QtCore, QtNetwork

CWD    = os.path.dirname(os.path.abspath(__file__))
PYFARM = os.path.abspath(os.path.join(CWD, "..", "..", ".."))
MODULE = os.path.basename(__file__)
if PYFARM not in sys.path: sys.path.append(PYFARM)

from lib import logger
logger = logger.Logger()

class RPCClient(QtCore.QObject):
    def __init__(self, parent=None):
        super(RPCClient, self).__init__(parent)

    def run(self):
        try:
            logger.info("Testing http://127.0.0.1:54000")
            client = xmlrpclib.ServerProxy("http://127.0.0.1:54000", verbose=False)

            data = {
                        "strA"  : "hello",
                        "intA"  : 1.0,
                        "dictA" : {
                                    "entryAA" : True,
                                    "entryAB" : 1,
                                    "entryAC" : 1.0,
                                    "dictAD"  : {
                                                    "itemA"     : True,
                                                    "itemB"     : "hi there",
                                                    "emptyDict" : {}
                                                 }
                                  }
                    }
            print client.echo(data, 2.0, "thirdInput")

            sys.exit()

        except Exception, error:
            logger.error("EXCEPTION: %s" % error)
            sys.exit(1)


if __name__ == '__main__':
    logger.info("Starting: %i" % os.getpid())
    app    = QtCore.QCoreApplication(sys.argv)
    client = RPCClient()
    client.run()
    sys.exit(app.exec_())