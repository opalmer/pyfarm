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

import random
from utcore import TestCase

from pyfarm.flaskapp import app, db
from pyfarm.ext.config.enum import AgentState
from pyfarm.models.agent import Agent


class DBAgentTestCase(TestCase):
    @classmethod
    def setUpCase(cls):
        app.test_client()
        # TODO: flask app not running!
        db.drop_all()
        db.create_all()

        Session = db.sessionmaker()
        cls.session = Session()
        cls.agent_state = AgentState()
        cls.agent = Agent()

    @classmethod
    def randip(cls):
        return ".".join(map(str,
                            [random.randint(0, 255), random.randint(0, 255),
                            random.randint(0, 255), random.randint(0, 255)]))

class TestAgentModel(DBAgentTestCase):
    def test_true(self):

        self.session.add(self.agent)
        self.session.commit()
        print self.agent.id

        # self.assertTrue(True)