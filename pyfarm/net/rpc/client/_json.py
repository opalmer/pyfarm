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
