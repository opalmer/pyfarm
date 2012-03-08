#!/usr/bin/env python
#
# This file is part of PyFarm.
# Copyright (C) 2008-2012 Oliver Palmer
#
# PyFarm is free software: you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# PyFarm is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.

'''simple tests'''

import socket

from common import logger

from twisted.python import log
from twisted.web import http
from twisted.internet import reactor

PORT = 9025
HOSTNAME = socket.getfqdn()
pages = {
    "/" :'''<html>
    <h2>Logs</h2>
    <a href="http://%s:%i/log/service">Service Log</a>
    <a href="http://%s:%i/log/jobs">Job Logs</a>
    </html>
    ''' % (HOSTNAME, PORT, HOSTNAME, PORT)
}

class RequestHandler(http.Request):
    def process(self):
        if self.path in pages:
            self.write(pages[self.path])

        else:
            self.setResponseCode(http.NOT_FOUND)
            self.write("no such page %s" % self.path)

        self.finish()
    # end process
# end RequestHandler

class HttpChannel(http.HTTPChannel):
    requestFactory = RequestHandler
# end HttpChannel

class HttpFactory(http.HTTPFactory):
    protocol = HttpChannel
# end HttpFactory

if __name__ == '__main__':
    factory = HttpFactory()
    reactor.listenTCP(PORT, factory)
    reactor.run()
