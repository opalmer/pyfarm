'''
HOMEPAGE: www.pyfarm.net
INITIAL: August 27 2009
PURPOSE: Asset server used to serve files to other components of PyFarm

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

import BaseHTTPServer

class AssetServer(object):
    '''Asset server class used to serve files to connecting clients'''
    def __init__(self, port, address=''):
        self.handler = BaseHTTPServer.BaseHTTPRequestHandler
        self.server = BaseHTTPServer.HTTPServer
        self.address = (address, port)
        
    def run(self):
        '''Run the AssetServer'''
        self.httpd = self.server(self.address, self.handler)
        self.httpd.serve_forever()
