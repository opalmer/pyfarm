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

from pyfarm.admin.db.agent import AgentModelView
from pyfarm.admin.db.task import TaskModelView

if __name__ == '__main__':
    import random
    from flask.ext.admin import Admin

    from pyfarm.utility import randstr, randint
    from pyfarm.models.agent import Agent
    from pyfarm.flaskapp import app, db
    from pyfarm.config.enum import OperatingSystem

    admin = Admin(app, name="PyFarm")
    admin.add_view(AgentModelView())
    admin.add_view(TaskModelView())


    db.drop_all()
    db.create_all()

    Session = db.sessionmaker()
    session = Session()


    ######################################################
    randi = lambda: random.randint(0, 255)
    randip = lambda: ".".join(map(str, [randi(), randi(), randi(), randi()]))

    OS_ENUM = OperatingSystem()

    for i in xrange(5000):
        agent = Agent()
        agent.ip = randip()
        agent.subnet = randip()
        agent.hostname = randstr()
        agent.id = randint()
        agent.state = random.randint(14, 17)
        agent.cpus = random.randint(1, 4)
        agent.ram = random.randint(50, 5000)
        agent.port = random.randint(1025, 65500)
        agent.os = random.choice(OS_ENUM.values())
        db.session.add(agent)

    db.session.commit()
    ######################################################

    app.run(debug=True)