'''
AUTHOR: Oliver Palmer
HOMEPAGE: www.pyfarm.net
INITIAL: April 7 2008
PURPOSE: Group of classes dedicated to the collection and monitoring
of status information.

    This file is part of PyFarm.

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
class StatusServerThread(QThread):
    '''
    Status server thread spawned upon every incoming connection to
    prevent collisions.
    '''
    pass


class StatusServer(QTcpServer):
    '''
    Main status server used to hold, gather, and update status
    information on a network wide scale.  Examples include notifying
    the main gui of a finished frame, addition of a host to the network, and
    other similiar functions.  See StatusServerThread for the server logic.
    '''
    pass


class StatusClient(QObject):
    '''
    Status client used to connect to a status server and
    exchange information.
    '''
    pass
