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

"""
handler module used to handle incoming requests
"""

from twisted.web import http
from twisted.internet import reactor

class Request(http.Request):
    """
    Request handler which process the request directly before
    returning results.  Typically this class will be inherited
    and have paths to incoming requests and their targets placed
    in self.pages.
    """
    pages = {}

    def setPages(self, pages):
        self.pages = pages

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


def CreateFactory(request_handler):
    """creates a HTTPFactory based on a provided handler"""
    class Channel(http.HTTPChannel):
        requestFactory = request_handler
    # end Channel

    class Factory(http.HTTPFactory):
        protocol = Channel
    # end Factory

    return Factory()
# end CreateFactory
