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

from datetime import datetime
from werkzeug.http import http_date
from flask import request
from flask.ext import restful
from pyfarm.ext.system import netinfo


class Ping(restful.Resource):
    def _agentInformation(self, request):
        """
        Returns information about the agent making a request
        """
        # TODO: call underlying api (same api /agents/ will use)
        return None

    def get(self):
        """
        When queried this method returns some general information about the
        server and requesting agent.

        ::

            GET 200 /ping
            {
                "master_name": <name of master serving request>,
                "remote_addr": <address of host requesting information>,
                "agent": <href to additional information about the agent>,
                "time": <current time since the epoc as a float>
            }
        """
        return {
            "master_name": netinfo.hostname(),
            "remote_addr": request.remote_addr,
            "agent": self._agentInformation(request),
            "time": http_date(datetime.now())
        }