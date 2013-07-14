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

from __future__ import with_statement
from utcore import ModelTestCase, unique_ip, unique_str

from pyfarm.flaskapp import app, db
from pyfarm.ext.config.enum import AgentState
from pyfarm.models.agent import Agent


class TestAgentModel(ModelTestCase):
    subnet = "255.255.255.0"

    def test_basic_insert(self):
        hostname = "foobar"
        address = unique_ip()
        agent = Agent(hostname, address, self.subnet)
        self.assertIsNone(agent.id)
        db.session.add(agent)
        db.session.commit()
        self.assertIsInstance(agent.id, long)
        result = Agent.query.filter_by(id=agent.id).first()
        self.assertEqual(result.id, agent.id)
        self.assertEqual(result.hostname, agent.hostname)
        self.assertEqual(result.ip, address)

    def test_hostname_validation(self):
        with self.assertRaises(ValueError):
            Agent("foo/bar", unique_ip(), self.subnet)

        with self.assertRaises(ValueError):
            Agent("", unique_ip(), self.subnet)

        Agent("foo-bar", unique_ip(), self.subnet)

    def test_ip_validation(self):
        fail_addresses = (
            "0.0.0.0", "127.0.0.1", "169.254.0.1",
            "224.0.0.0", "255.255.255.255"
        )
        for address in fail_addresses:
            with self.assertRaises(ValueError):
                Agent("foobar", address, unique_ip())

    # def test_subnet_validation(self):
    #     subnets