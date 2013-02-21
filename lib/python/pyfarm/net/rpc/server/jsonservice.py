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

from twisted.internet import defer
from twisted.python import log
from twisted.web import server

from txjsonrpc import jsonrpclib
from txjsonrpc.web import jsonrpc
from txjsonrpc.web.jsonrpc import Handler

from pyfarm.net.rpc import error

class JSONServer(jsonrpc.JSONRPC):
    """
    Overrides a few methods provided by
    :py:class:`txjsonrpc.web.jsonrpc.JSONRPC` so we can properly handle
    errors and support keyword arguments.
    """
    def render(self, request):
        request.content.seek(0, 0)
        args = request.args
        content = request.content.read()
        self.callback = None

        if not content and request.method == 'GET' and 'request' in args:
            content = args['request'][0]

        if 'callback' in args:
            self.callback = args['callback'][0]

        self.is_jsonp = True if self.callback else False

        # parse the incoming content and get the method
        # name, arguments, and keywords
        parsed = jsonrpclib.loads(content)
        functionPath = parsed.get("method")
        args = parsed.get('params', [])
        kwargs = parsed.get('kwargs', {})
        id = parsed.get('id')

        # retrieve the version from the data
        version = parsed.get('jsonrpc')
        if version: version = int(float(version))
        elif id and not version: version = jsonrpclib.VERSION_1
        else: version = jsonrpclib.VERSION_PRE1

        # retrieve the function,
        try:
            function = self._getFunction(functionPath)
        except jsonrpclib.Fault, f:
            self._cbRender(f, request, id, version)
        else:
            if not self.is_jsonp:
                request.setHeader("content-type", "text/json")
            else:
                request.setHeader("content-type", "text/javascript")

            # execute the function
            d = defer.maybeDeferred(function, *args, **kwargs)
            d.addErrback(self._ebRender, id)
            d.addCallback(self._cbRender, request, id, version)

        return server.NOT_DONE_YET
    # end render

    def _getFailure(self, failure):
        msg = str(failure.value)
        if isinstance(failure.value, TypeError) and "unexpected keyword" in msg:
            return error.INVALID_KEYWORD, msg

        return error.FAULT, msg
    # end _getFailure

    def _getJsonString(self, data, id, version):
        if not self.is_jsonp:
            return jsonrpclib.dumps(data, id=id, version=version)
        else:
            return "%s(%s)" % (
                self.callback, jsonrpclib.dumps(data, id=id, version=version)
            )
    # end _getJsonString

    def _cbRender(self, result, request, id, version):
        if isinstance(result, Handler):
            result = result.result

        if version == jsonrpclib.VERSION_PRE1 and not isinstance(result, jsonrpclib.Fault):
            result = (result,)

        try:
            s = self._getJsonString(result, id, version)

        except Exception, e:
            log.err(e)
            f = jsonrpclib.Fault(error.ERROR_WHILE_DUMPING, str(error))
            s = self._getJsonString(f, id, version)

        # write the results back
        request.setHeader("content-length", str(len(s)))
        request.write(s)
        request.finish()
    # end _cbRender

    def _ebRender(self, failure, id):
        if isinstance(failure.value, jsonrpclib.Fault):
            return failure.value

        log.err(failure)
        code, message = self._getFailure(failure)
        return jsonrpclib.Fault(code, message)
    # end _ebRender
# end JSONServer
