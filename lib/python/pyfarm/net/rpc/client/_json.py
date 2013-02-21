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

from uuid import uuid4
from urllib import urlopen
from json import dumps, loads

from pyfarm.errors import RPCFault


class JSONRPCClient(object):
    """
    Remote procedure call client class which operates on JSON.

    :param string url:
        The url to connect to.  This string may also contain login
        information and a port

    :param boolean uid:
        If True then send along a unique identifier with each
        message
    """
    def __init__(self, url, uid=True, method=None):
        self.__url = url
        self.__method = method
        self.__uid = uid
    # end __init__

    def __getattr__(self, name):
        return JSONRPCClient(
            self.__url,
            method=".".join((self.__method, name)) if self.__method is not None
            else name
        )
    # end __getattr__

    @property
    def basepostdata(self):
        """generates the base data to post"""
        data = {
            'id' : 'jsonrpc',
            'method' : self.__method
        }

        if self.__uid:
            data['uid'] = str(uuid4())

        return data
    # end basepostdata

    def __call__(self, *args, **kwargs):
        # create the data
        data = self.basepostdata
        data['params'] = args
        data['kwargs'] = kwargs

        # produce json string and post it to the remote
        # server
        response_data = urlopen(self.__url, dumps(data)).read()
        response = loads(response_data)

        # check for and reraise the error if something
        # went wrong
        if response['error'] is not None:
            code = response['error']['faultCode']
            message = response['error']['faultString']
            raise RPCFault(code, message)

        return response['result']
    # end __call__
# end JSONRPCClient
