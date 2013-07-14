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

import re
import netaddr
from sqlalchemy.orm import validates

from pyfarm.flaskapp import db
from pyfarm.ext.config.core.loader import Loader
from pyfarm.ext.config.enum import AgentState
from pyfarm.ext.system.network import IP_NONNETWORK
from pyfarm.models.mixins import StateValidationMixin, RandIdMixin
from pyfarm.models.constants import (
    DBCFG, TABLE_AGENT, TABLE_AGENT_TAGS, TABLE_AGENT_SOFTWARE)

REGEX_HOSTNAME = re.compile("^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*"
                            "[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9]"
                            "[A-Za-z0-9\-]*[A-Za-z0-9])$")


class AgentTagsModel(db.Model):
    """Table model used to store tags for agents"""
    __tablename__ = TABLE_AGENT_TAGS
    _agentid = db.Column(db.Integer, db.ForeignKey("%s.id" % TABLE_AGENT),
                         primary_key=True)
    tag = db.Column(db.String)


class AgentSoftwareModel(db.Model):
    """Table model used to store tags for agents"""
    __tablename__ = TABLE_AGENT_SOFTWARE
    _agentid = db.Column(db.Integer, db.ForeignKey("%s.id" % TABLE_AGENT),
                         primary_key=True)
    software = db.Column(db.String)


class AgentModel(db.Model, RandIdMixin, StateValidationMixin):
    """
    Stores information about an agent include its network address,
    state, allocation configuration, etc.
    """
    __tablename__ = TABLE_AGENT
    STATE_ENUM = AgentState()
    STATE_DEFAULT = STATE_ENUM.ONLINE

    # NOTE: any columns where are null will generally be filled in
    # by the client on first connect

    # host state information
    enabled = db.Column(db.Boolean, default=True)
    state = db.Column(db.Integer, default=STATE_DEFAULT, nullable=False)
    hostname = db.Column(db.String, nullable=False)
    ip = db.Column(db.String(15), nullable=False)
    subnet = db.Column(db.String(15), nullable=False)
    ram = db.Column(db.Integer)
    cpus = db.Column(db.Integer)
    port = db.Column(db.Integer)

    # Max allocation of the two primary resources which `1.0` is 100%
    # allocation.  For `cpu_allocation` 100% allocation typically means
    # one task per cpu.
    ram_allocation = db.Column(db.Float,
                               default=DBCFG.get("agent.ram_allocation", .8))
    cpu_allocation = db.Column(db.Float,
                               default=DBCFG.get("agent.cpu_allocation", 1.0))

    # relationships
    tasks = db.relationship("TaskModel", backref="agent", lazy="dynamic")
    tags = db.relationship("AgentTagsModel", backref="agent", lazy="dynamic")
    software = db.relation("AgentSoftwareModel", backref="agent", lazy="dynamic")

    @validates("hostname")
    def validate_hostname(self, key, value):
        # ensure hostname does not contain characters we can't use
        if not REGEX_HOSTNAME.match(value):
            raise ValueError("%s is not valid for %s" % (value, key))

        return value

    @validates("ip", "subnet")
    def validate_address(self, key, value):
        try:
            ip = netaddr.IPAddress(value)

        except ValueError, e:
            raise ValueError(
                "%s is not a valid address format: %s" % (value, e))

        if key == "ip" and ip in IP_NONNETWORK:
            raise ValueError(
                "%s is not a valid address for `ip`" % value)

        # TODO: add test for subnet
        # elif key == "subnet" and ip.iptype() != "RESERVED":
        #     raise ValueError("subnet is not valid")

        return value

    @validates("ram", "cpus", "port")
    def validate_resource(self, key, value):
        if value is None:
            return value

        min_value = DBCFG.get("agent.min_%s" % key)
        max_value = DBCFG.get("agent.max_%s" % key)

        # quick sanity check of the incoming config
        assert isinstance(min_value, int), "db.min_%s must be an integer" % key
        assert isinstance(max_value, int), "db.max_%s must be an integer" % key
        assert min_value >= 1, "db.min_%s must be > 0" % key
        assert max_value >= 1, "db.max_%s must be > 0" % key

        # check the provided input
        if min_value > value or value > max_value:
            msg = "value for `%s` must be between " % key
            msg += "%s and %s" % (min_value, max_value)
            raise ValueError(msg)

        return value


class Agent(AgentModel):
    """
    Provides :meth:`__init__` for :class:`AgentModel` so the model can
    be instanced with initial values.
    """
    def __init__(self, hostname, ip, subnet, state=None, enabled=None,
                 ram=None, cpus=None, port=None, ram_allocation=None,
                 cpu_allocation=None):
        self.hostname = hostname
        self.ip = ip
        self.subnet = subnet

        if state is not None:
            self.state = state

        if enabled is not None:
            self.enabled = enabled

        if ram is not None:
            self.ram = ram

        if cpus is not None:
            self.cpus = cpus

        if port is not None:
            self.port = port

        if ram is not None:
            self.ram_allocation = ram_allocation

        if cpu_allocation is not None:
            self.cpu_allocation = cpu_allocation