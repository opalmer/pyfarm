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

from utcore import ModelTestCase, random_private_ip

from pyfarm.flaskapp import app, db
from pyfarm.ext.config.enum import AgentState
from pyfarm.models.agent import AgentModel

print "=" * 50
print "create interface classes for the models"
print "=" * 50
raise NotImplementedError("create interface classes for the models")

class TestAgentModel(ModelTestCase):
   def test_true(self):
       agent = AgentModel()
       db.session.add(agent)
       db.session.commit()