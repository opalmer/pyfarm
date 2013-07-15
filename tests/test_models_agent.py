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
import random

from utcore import ModelTestCase, unique_ip
from pyfarm.flaskapp import db
from pyfarm.models.agent import Agent, AgentSoftware
from pyfarm.models.constants import DBCFG


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
            "0.0.0.0",
            "169.254.0.0", "169.254.254.255",  # link local
            "127.0.0.1", "127.255.255.255",  # loopback
            "224.0.0.0", "255.255.255.255",  # multi/broadcast
            "255.0.0.0", "255.255.0.0"
        )
        for address in fail_addresses:
            with self.assertRaises(ValueError):
                Agent("foobar", address, "255.0.0.0")

    def test_subnet_validation(self):
        fail_subnets = (
            "0.0.0.0",
            "169.254.0.0", "169.254.254.255",  # link local
            "127.0.0.1", "127.255.255.255",  # loopback
            "224.0.0.0", "255.255.255.255",  # multi/broadcast
            "10.56.0.1", "172.16.0.1"

        )
        for address in fail_subnets:
            with self.assertRaises(ValueError):
                Agent("foobar", "10.56.0.1", address)

    def test_resource_validation(self):
        for resource in ("ram", "cpus", "port"):
            min_value = DBCFG.get("agent.min_%s" % resource)
            max_value = DBCFG.get("agent.max_%s" % resource)
            values = range(min_value, max_value + 1)
            value = random.choice(values)

            kwargs = {resource: value}
            Agent("foobar", "10.56.0.1", "255.0.0.0", **kwargs)

            kwargs = {resource: None}
            agent = Agent("foobar", "10.56.0.1", "255.0.0.0", **kwargs)
            self.assertIsNone(getattr(agent, resource))

            with self.assertRaises(ValueError):
                kwargs = {resource: min_value - 1}
                Agent("foobar", "10.56.0.1", "255.0.0.0", **kwargs)

            with self.assertRaises(ValueError):
                kwargs = {resource: max_value + 1}
                Agent("foobar", "10.56.0.1", "255.0.0.0", **kwargs)

    def test_software(self):
        # create the agent
        agent_foobar = Agent("foobar", "10.56.0.1", "255.0.0.0")
        db.session.add(agent_foobar)
        db.session.commit()

        # create some software tags
        software_objects = []
        for software_name in ("foo", "bar", "baz"):
            software = AgentSoftware(agent_foobar, software_name)
            software_objects.append(software)
            db.session.add(software)

        db.session.commit()
        agent = Agent.query.filter_by(id=agent_foobar.id).first()

        # agent.software == software_objects
        self.assertEqual(
            set(i.id for i in agent.software.all()),
            set(i.id for i in software_objects))

        # same as above, asking from the software table side
        self.assertEqual(
            set(i.id for i in AgentSoftware.query.filter_by(agent=agent).all()),
            set(i.id for i in software_objects))