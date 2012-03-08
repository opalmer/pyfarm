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

from __future__ import with_statement

import socket

from common import logger

from twisted.python import log
from twisted.web import http
from twisted.internet import reactor

PORT = 9025
HOSTNAME = socket.getfqdn()

from common.web import handler, html

class RequestHandler(handler.Request):
    pages = {
        "/" : "html_root",
        "/log/service" : "html_log_service",
        "/log/jobs" : "html_log_jobs"
    }

    def html_log_jobs(self):
        with html.Page("PyFarm - Job Logs - %s" % HOSTNAME, PORT) as page:
            page.add("Nothing here yet.")
        return page.content
    # end html_log_jobs

    def html_log_service(self):
        with html.Page("PyFarm - Service Log - %s" % HOSTNAME, PORT) as page:
            page.add("Nothing here yet.")
        return page.content
    # end html_log_service

    def html_root(self):
        with html.Page("PyFarm - Host Interface - %s" % HOSTNAME, PORT) as page:
            with html.Tag(page, "p"):
                page.link("%s:%i/log/service" % (HOSTNAME, PORT), "Service Log")
                page.link("%s:%i/log/jobs" % (HOSTNAME, PORT), "Job Logs")

        return page.content
    # end html_root
# end RequestHandler

if __name__ == '__main__':
    factory = handler.CreateFactory(RequestHandler)
    reactor.listenTCP(PORT, factory)
    reactor.run()
