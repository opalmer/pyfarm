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
