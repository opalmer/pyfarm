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

import os
try:
    import json
except ImportError:
    import simplejson as json

from flask import Flask
from flask.ext.sqlalchemy import SQLAlchemy
from flask.ext import admin, wtf
from flask import Flask

from flask.ext.classy import FlaskView

class AgentView(FlaskView):
    def agent(self):
        # TODO: this is probably not returning JSON
        return json.dumps({os.environ.data})



def master():
    app = Flask(__name__)
    AgentView.register(app)
    app.run()