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

class Tag(object):
    '''simple class for opening and closing tags'''
    def __init__(self, page, tag):
        self.page = page
        self.tag = tag

    def __enter__(self): self.page.add("<%s>" % self.tag)
    def __exit__(self, type, value, traceback): self.page.add("</%s>" % self.tag)
# end Tag

class Page(object):
    '''basic class to assist in building a webpage'''
    def __init__(self, title):
        self.content = "<html><head><title>%s</title></head><body>" % title
    # end __init__

    def add(self, other):
        self.__add__(other)
    # end add

    def link(self, url, name=None, br=True):
        '''inserts a url'''
        content = ""
        if br:
            content += "<br />"
        content += '<a href="http://%s">' % url
        if name is not None:
            content += "%s" % name
        else:
            content += "%s" % url
        content += "</a>"
        self.add(content)
    # end link

    def br(self): self.add("<br />")
    def header(self, msg, size="h4"): self.add("<%s>%s</%s>" % (size, msg, size))

    def __add__(self, other):
        self.content += "\n%s" % other
    # end __init__

    def __enter__(self):
        return self
    # end __enter__

    def __exit__(self, type, value, traceback):
        self.link("%s:%s" % (HOSTNAME, PORT), "Home")
        self.add("</body></html>")
    # end __exit__
# end Page

class RequestHandler(http.Request):
    pages = {
        "/" : "html_root",
        "/log/service" : "html_log_service",
        "/log/jobs" : "html_log_jobs"
    }
    def setPages(self, pages):
        self.pages = pages

    def html_log_jobs(self):
        with Page("PyFarm - Job Logs - %s" % HOSTNAME) as page:
            page.add("Nothing here yet.")
        return page.content
    # end html_log_jobs

    def html_log_service(self):
        with Page("PyFarm - Service Log - %s" % HOSTNAME) as page:
            page.add("Nothing here yet.")
        return page.content
    # end html_log_service

    def html_root(self):
        with Page("PyFarm - Host Interface - %s" % HOSTNAME) as page:
            with Tag(page, "p"):
                page.link("%s:%i/log/service" % (HOSTNAME, PORT), "Service Log")
                page.link("%s:%i/log/jobs" % (HOSTNAME, PORT), "Job Logs")

        return page.content

    def process(self):
        self.setHeader('Content-Type', 'text/html')
        if self.path in self.pages:
            method_name = self.pages[self.path]

            # ensure the method is callable
            builder = getattr(self, method_name)
            if callable(builder):
                html = builder()
                self.write(html)
            else:
                self.setResponseCode(
                    http.INTERNAL_SERVER_ERROR,
                    "%s is not callable" % method_name
                )

        else:
            msg = "%s is not handled or does not exist" % self.path
            self.setResponseCode(http.NOT_FOUND, msg)
            self.write(msg)

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
