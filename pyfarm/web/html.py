# No shebang line, this module is meant to be imported
#
# Copyright 2013 Oliver Palmer
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#    http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

"""
classes for building html pages
"""

import socket

HOSTNAME = socket.getfqdn()

class Tag(object):
    """simple class for opening and closing tags"""
    def __init__(self, page, tag):
        self.page = page
        self.tag = tag

    def __enter__(self): self.page.add("<%s>" % self.tag)
    def __exit__(self, type, value, traceback): self.page.add("</%s>" % self.tag)
# end Tag

class Page(object):
    """basic class to assist in building a webpage"""
    def __init__(self, title, port, hostname=None):
        self.content = "<html><head><title>%s</title></head><body>" % title
        self.port = port
        self.hostname = hostname or HOSTNAME
    # end __init__

    def add(self, other):
        self.__add__(other)
    # end add

    def link(self, url, name=None, br=True):
        """inserts a url"""
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

