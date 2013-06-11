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

from pyfarm.admin.tables.job import (
    JobModelView, JobTagsModelView, JobSoftwareModelView
)
from pyfarm.admin.tables.agent import (
    AgentModelView, AgentTagsModelView, AgentSoftwareModelView
)
from pyfarm.admin.tables.task import TaskModelView

if __name__ == '__main__':
    import random
    from flask.ext.admin import Admin

    from pyfarm.config.enum import AgentState
    from pyfarm.utility import randstr, randint
    from pyfarm.models.agent import Agent
    from pyfarm.flaskapp import app, db

    db.drop_all()
    db.create_all()

    Session = db.sessionmaker()
    session = Session()
    agent_state = AgentState()

    ######################################################
    randi = lambda: random.randint(0, 255)
    randip = lambda: ".".join(map(str, [randi(), randi(), randi(), randi()]))

    for i in xrange(5):
        agent = Agent()
        agent.ip = randip()
        agent.subnet = randip()
        agent.hostname = randstr()
        agent.id = randint()
        agent.state = random.choice(agent_state.values())
        db.session.add(agent)

    db.session.commit()
    ######################################################

    admin = Admin(app, name="PyFarm")
    admin.add_view(AgentModelView())
    admin.add_view(AgentTagsModelView())
    admin.add_view(AgentSoftwareModelView())
    admin.add_view(JobModelView())
    admin.add_view(JobTagsModelView())
    admin.add_view(JobSoftwareModelView())
    admin.add_view(TaskModelView())

    app.run(debug=True)