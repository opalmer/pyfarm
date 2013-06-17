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
Contains the base Flask application setup.  This module is also the
location that sets up the initial database connection.  This is done so that
both developers and running unittests can intercept the configuration and
add their own.  After setting up the application :func:`pyfarm.run.run`
will handle the execution of the Flask application
"""

import uuid
from flask import Flask
from flask.ext.sqlalchemy import SQLAlchemy

from pyfarm.error import SubKeyError, PreferencesError
from pyfarm.config.database import DBConfig

app = Flask("PyFarm")
dbconfig = DBConfig()

# iterate over configuration names we should expect to find
for config_name in dbconfig.get("config_order"):
    try:
        dburi = dbconfig.url(config_name)
    except SubKeyError:
        continue
    else:
        break
else:
    # there's something wrong with the setup if we reach this point
    raise PreferencesError("failed to find any database configurations")

app.config["SQLALCHEMY_DATABASE_URI"] = dburi
app.secret_key = str(uuid.uuid4())  # TODO: this needs a config or extern lookup

db = SQLAlchemy(app)
