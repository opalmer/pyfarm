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
from uuid import UUID
from sqlalchemy.exc import IntegrityError
from utcore import ModelTestCase
from pyfarm.ext.config.core.loader import Loader
from pyfarm.ext.config.enum import AgentState
from pyfarm.flaskapp import db
from pyfarm.models.agent import Agent, AgentSoftware, AgentTag

try:
    from itertools import product
except ImportError:
    from pyfarm.backports import product

STATE_ENUM = AgentState()
DBCFG = Loader("dbdata.yml")


class AgentTestCase(ModelTestCase):
    hostnamebase = "foobar"
    ports = (DBCFG.get("agent.min_port"), DBCFG.get("agent.max_port"))
    cpus = (DBCFG.get("agent.min_cpus"), DBCFG.get("agent.max_cpus"))
    ram = (DBCFG.get("agent.min_ram"), DBCFG.get("agent.max_ram"))
    states = STATE_ENUM.values()

    # static test values
    _port = ports[-1]
    _ram = ram[-1]
    _cpus = cpus[-1]
    _host = hostnamebase
    _ip = "10.0.0.0"
    _subnet = "255.0.0.0"

    # General list of addresses we should test
    # against.  This covered the start and end
    # points for all private network ranges.
    addresses = (
        ("10.0.0.0", "255.0.0.0"),
        ("172.16.0.0", "255.240.0.0"),
        ("192.168.0.0", "255.255.255.0"),
        ("10.255.255.255", "255.0.0.0"),
        ("172.31.255.255", "255.240.0.0"),
        ("192.168.255.255", "255.255.255.0"))

    def agentModelArgs(self):
        generator = product(self.addresses, self.ports, self.cpus, self.ram)

        count = 0
        for address, port, cpus, ram in generator:
            ip, subnet = address
            hostname = "%s%02d" % (self.hostnamebase, count)
            yield (hostname, ip, subnet, port, cpus, ram)
            count += 1

    def agents(self):
        """
        Iterates over the class level variables and produces an agent
        model.  This is done so that we test endpoints in the extreme ranges.
        """
        for args in self.agentModelArgs():
            yield Agent(*args)


class TestAgentSoftware(AgentTestCase):
    def test_software(self):
        # create the agent
        agent_foobar = Agent(self._host, self._ip, self._subnet, self._port,
                             self._cpus, self._ram)
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


class TestAgentTags(AgentTestCase):
    def test_tags(self):
        # create the agent
        agent_foobar = Agent(self._host, self._ip, self._subnet, self._port,
                             self._cpus, self._ram)
        db.session.add(agent_foobar)
        db.session.commit()

        # create some software tags
        tag_objects = []
        for tag_name in ("foo", "bar", "baz"):
            tag = AgentTag(agent_foobar, tag_name)
            tag_objects.append(tag)
            db.session.add(tag)

        db.session.commit()
        agent = Agent.query.filter_by(id=agent_foobar.id).first()

        # agent.software == software_objects
        self.assertEqual(
            set(i.id for i in agent.tags.all()),
            set(i.id for i in tag_objects))

        # same as above, asking from the software table side
        self.assertEqual(
            set(i.id for i in AgentTag.query.filter_by(agent=agent).all()),
            set(i.id for i in tag_objects))


class TestAgentModel(AgentTestCase):
    def test_basic_insert(self):
        for hostname, ip, subnet, port, cpus, ram in self.agentModelArgs():
            model = Agent(hostname, ip, subnet, port, cpus, ram)
            db.session.add(model)
            self.assertEqual(model.hostname, hostname)
            self.assertEqual(model.ip, ip)
            self.assertEqual(model.subnet, subnet)
            self.assertEqual(model.port, port)
            self.assertEqual(model.cpus, cpus)
            self.assertEqual(model.ram, ram)
            self.assertIsNone(model.id)
            db.session.commit()
            self.assertIsInstance(model.id, UUID)
            result = Agent.query.filter_by(id=model.id).first()
            self.assertEqual(model.hostname, result.hostname)
            self.assertEqual(model.ip, result.ip)
            self.assertEqual(model.subnet, result.subnet)
            self.assertEqual(model.port, result.port)
            self.assertEqual(model.cpus, result.cpus)
            self.assertEqual(model.ram, result.ram)
            self.assertEqual(result.id, model.id)

    def test_basic_insert_nonunique(self):
        modelA = Agent(self._host, self._ip, self._subnet, self._port,
                       self._cpus, self._ram)
        modelB = Agent(self._host, self._ip, self._subnet, self._port,
                       self._cpus, self._ram)
        db.session.add(modelA)
        db.session.add(modelB)

        with self.assertRaises(IntegrityError):
            db.session.commit()

        db.session.rollback()
        modelB = Agent(self._host, self._ip, self._subnet, self._port-1,
                       self._cpus, self._ram)
        db.session.add(modelA)
        db.session.add(modelB)
        db.session.commit()

    def test_hostname_validation(self):
        with self.assertRaises(ValueError):
            Agent("foo/bar", self._ip, self._subnet, self._port,
                  self._cpus, self._ram)

        with self.assertRaises(ValueError):
            Agent("", self._ip, self._subnet, self._port,
                  self._cpus, self._ram)

        Agent("foo-bar", self._ip, self._subnet, self._port,
              self._cpus, self._ram)

    def test_ip_validation(self):
        fail_addresses = (
            "0.0.0.0",
            "169.254.0.0", "169.254.254.255",  # link local
            "127.0.0.1", "127.255.255.255",  # loopback
            "224.0.0.0", "255.255.255.255",  # multi/broadcast
            "255.0.0.0", "255.255.0.0")

        for address in fail_addresses:
            with self.assertRaises(ValueError):
                Agent(self._host, address, self._subnet, self._port,
                      self._cpus, self._ram)

    def test_subnet_validation(self):
        fail_subnets = (
            "0.0.0.0",
            "169.254.0.0", "169.254.254.255",  # link local
            "127.0.0.1", "127.255.255.255",  # loopback
            "224.0.0.0", "255.255.255.255",  # multi/broadcast
            "10.56.0.1", "172.16.0.1")

        for subnet in fail_subnets:
            with self.assertRaises(ValueError):
                Agent(self._host, self._ip, subnet, self._port,
                      self._cpus, self._ram)

    def test_resource_validation(self):
        for hostname, ip, subnet, port, cpus, ram in self.agentModelArgs():
            model = Agent(hostname, ip, subnet, port, cpus, ram)
            db.session.add(model)
            db.session.commit()

            # port value test
            if port == DBCFG.get("agent.min_port"):
                with self.assertRaises(ValueError):
                    Agent(hostname, ip, subnet, port-1, cpus, ram)
            else:
                with self.assertRaises(ValueError):
                    Agent(hostname, ip, subnet, port+1, cpus, ram)

            # cpu value test
            if cpus == DBCFG.get("agent.min_cpus"):
                with self.assertRaises(ValueError):
                    Agent(hostname, ip, subnet, port, cpus-1, ram)
            else:
                with self.assertRaises(ValueError):
                    Agent(hostname, ip, subnet, port, cpus+1, ram)

            # ram value test
            if ram == DBCFG.get("agent.min_ram"):
                with self.assertRaises(ValueError):
                    Agent(hostname, ip, subnet, port, cpus, ram-1)
            else:
                with self.assertRaises(ValueError):
                    Agent(hostname, ip, subnet, port, cpus, ram+1)
