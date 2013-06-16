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
import ipaddress
from warnings import warn
from sqlalchemy.orm import validates

from pyfarm.flaskapp import db
from pyfarm.warning import NetworkWarning
from pyfarm.ext.config.core.loader import Loader
from pyfarm.ext.config.enum import AgentState
from pyfarm.ext.utility import isLocalIPv4Address
from pyfarm.models.mixins import StateValidationMixin, RandIdMixin
from pyfarm.models.constants import (
    DBCFG, TABLE_AGENT, TABLE_AGENT_TAGS, TABLE_AGENT_SOFTWARE)

REGEX_HOSTNAME = re.compile("^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*"
                            "[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9]"
                            "[A-Za-z0-9\-]*[A-Za-z0-9])$")


class AgentTags(db.Model):
    """Table model used to store tags for agents"""
    __tablename__ = TABLE_AGENT_TAGS
    _agentid = db.Column(db.Integer, db.ForeignKey("%s.id" % TABLE_AGENT),
                         primary_key=True)
    tag = db.Column(db.String)


class AgentSoftware(db.Model):
    """Table model used to store tags for agents"""
    __tablename__ = TABLE_AGENT_SOFTWARE
    _agentid = db.Column(db.Integer, db.ForeignKey("%s.id" % TABLE_AGENT),
                         primary_key=True)
    software = db.Column(db.String)


class Agent(db.Model, RandIdMixin, StateValidationMixin):
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
    tasks = db.relationship("Task", backref="agent", lazy="dynamic")
    tags = db.relationship("AgentTags", backref="agent", lazy="dynamic")
    software = db.relation("AgentSoftware", backref="agent", lazy="dynamic")

    @validates("hostname")
    def validate_hostname(self, key, value):
        # ensure hostname does not contain characters we can't use
        if not REGEX_HOSTNAME.match(value):
            raise ValueError("%s is not valid for %s" % (value, key))

        return value

    @validates("ip", "subnet")
    def validate_address(self, key, value):
        try:
            if isLocalIPv4Address(value):
                warn("non-public address %s provided" % value, NetworkWarning)

        except ipaddress.AddressValueError, e:
            raise ipaddress.AddressValueError(
                "%s is not a valid ip: %s" % (value, e))

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

