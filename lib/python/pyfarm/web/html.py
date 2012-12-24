# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2013 Oliver Palmer
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

'''
classes for building html pages
'''

import socket

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
    def __init__(self, title, port, hostname=None):
        self.content = "<html><head><title>%s</title></head><body>" % title
        self.port = port
        self.hostname = hostname or HOSTNAME
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
        self.link("%s:%s" % (self.hostname, self.port), "Home")
        self.add("</body></html>")
    # end __exit__
# end Page

