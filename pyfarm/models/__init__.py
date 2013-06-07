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
Contains all the models the master operates on.
"""

# NOTE: All models must be loaded here so the mapper
#       can create the relationships on startup
from pyfarm.models.task import Task
from pyfarm.models.agent import Agent


if __name__ == '__main__':
    from pyfarm.flaskapp import db, app
    from flask.ext.admin import Admin
    from flask.ext.admin.contrib.sqlamodel import ModelView

    db.drop_all()
    db.create_all()

    admin = Admin(app, name="PyFarm")
    admin.add_view(ModelView(Agent, db.session, category="Database"))
    admin.add_view(ModelView(Task, db.session, category="Database"))

    app.run(debug=True)
