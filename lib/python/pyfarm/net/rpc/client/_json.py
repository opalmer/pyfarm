# No shebang line, this module is meant to be imported
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

import pprint
from uuid import uuid4
from urllib import urlopen
from json import dumps, loads


class JSONRPCException(Exception):
    def __init__(self, rpcError):
        Exception.__init__(self)
        self.error = rpcError
    # end __init__
# end JSONRPCException


class JSONRPCClient(object):
    def __init__(self, serviceURL, serviceName=None):
        self.__serviceURL = serviceURL
        self.__serviceName = serviceName
    # end __init__

    def __getattr__(self, name):
        if self.__serviceName is not None:
            name = "%s.%s" % (self.__serviceName, name)
        return JSONRPCClient(self.__serviceURL, name)
    # __getattr__

    def _data(self, args, kwargs):
        return {
            'method': self.__serviceName,
            'args': args,
            'kwargs': kwargs,
            'uid': str(uuid4()),
#            'id': 'jsonrpc'
        }
    # end _data

    def __call__(self, *args, **kwargs):
        post = dumps(self._data(args, kwargs))

        try:
            response = urlopen(self.__serviceURL, post).read()

        except IOError:
            print "POST data: %s" % pprint.pformat(post)
            print "URL: %s" % self.__serviceURL
            raise

        try:
            resp = loads(response)

        except ValueError:
            print response
            raise

        # TODO: do something here if the remote has problems with the keywords
        if resp['error'] is not None:
            raise JSONRPCException(resp['error'])

        else:
            return resp['result']
    # end __call__
# end JSONRPCCliente
