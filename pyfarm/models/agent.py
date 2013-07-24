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
from textwrap import dedent
from sqlalchemy.schema import UniqueConstraint
from sqlalchemy.orm import validates

from pyfarm.flaskapp import db
from pyfarm.ext.config.core.loader import Loader
from pyfarm.ext.config.enum import AgentState
from pyfarm.ext.system.network import IP_NONNETWORK
from pyfarm.models.mixins import StateValidationMixin
from pyfarm.models.core import (
    DBCFG, TABLE_AGENT, TABLE_AGENT_TAGS, TABLE_AGENT_SOFTWARE, IDColumn)

REGEX_HOSTNAME = re.compile("^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*"
                            "[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9]"
                            "[A-Za-z0-9\-]*[A-Za-z0-9])$")


class AgentTaggingMixin(object):
    """
    Mixin used which provides some common structures to
    :class:`.AgentTagsModel` and :class:`.AgentSoftwareModel`
    """
    @validates("tag", "software")
    def validate_string_column(self, key, value):
        """
        Ensures `value` is a string or something that can be converted
        to a string.
        """
        if isinstance(value, (int, long)):
            value = str(value)
        elif not isinstance(value, basestring):
            raise ValueError("expected a string for `%s`" % key)

        return value


class AgentTagsModel(db.Model, AgentTaggingMixin):
    """
    Table model used to store tags for an agent.

    .. note::
        This table enforces two forms of uniqueness.  The :attr:`id` column
        must be unique and the combination of these columns must also be
        unique to limit the frequency of duplicate data:

            * :attr:`_agentid`
            * :attr:`tag`

    .. autoattribute:: _agentid
    """
    __tablename__ = TABLE_AGENT_TAGS
    __table_args__ = (UniqueConstraint("_agentid", "tag"), )
    id = IDColumn()
    _agentid = db.Column(db.Integer, db.ForeignKey("%s.id" % TABLE_AGENT),
                         doc=dedent("""
                         The foreign key which stores :attr:`AgentModel.id`"""))
    tag = db.Column(db.String,
                    doc=dedent("""
                    A string value to tag an :class:`.AgentModel`. Generally
                    this value is used for grouping like resources together
                    on the network but could also be used by jobs as a sort of
                    requirement."""))


class AgentTag(AgentTagsModel):
    """
    Provides :meth:`__init__` for :class:`.AgentTagsModel` so the model can
    be instanced with initial values.
    """
    def __init__(self, agent, tag):
        if isinstance(agent, Agent):
            agentid = agent.id
        else:
            agentid = agent

        self._agentid = agentid
        self.tag = tag


class AgentSoftwareModel(db.Model, AgentTaggingMixin):
    """
    Stores information about an the software installed on
    an agent.

    .. note::
        This table enforces two forms of uniqueness.  The :attr:`id` column
        must be unique and the combination of these columns must also be
        unique to limit the frequency of duplicate data:

            * :attr:`_agentid`
            * :attr:`version`
            * :attr:`software`

    .. autoattribute:: _agentid
    """
    __tablename__ = TABLE_AGENT_SOFTWARE
    __table_args__ = (UniqueConstraint("_agentid", "version", "software"), )
    id = IDColumn()
    _agentid = db.Column(db.Integer, db.ForeignKey("%s.id" % TABLE_AGENT),
                         doc=dedent("""
                         The foreign key which stores :attr:`AgentModel.id`"""))
    version = db.Column(db.String, default="any",
                        doc=dedent("""
                        The version of the software installed on a host.  This
                        value does not follow any special formatting rules
                        because the format depends on the 3rd party."""))
    software = db.Column(db.String,
                         doc=dedent("""
                         The name of the software installed.  No normalization
                         is performed prior to being stored in the database"""))


class AgentSoftware(AgentSoftwareModel):
    """
    Provides :meth:`__init__` for :class:`AgentSoftwareModel` so the model can
    be instanced with initial values.
    """
    def __init__(self, agent, software, version="any"):
        if isinstance(agent, Agent):
            agentid = agent.id
        else:
            agentid = agent

        self._agentid = agentid
        self.software = software
        self.version = version


class AgentModel(db.Model, StateValidationMixin):
    """
    Stores information about an agent include its network address,
    state, allocation configuration, etc.

    .. note::
        This table enforces two forms of uniqueness.  The :attr:`id` column
        must be unique and the combination of these columns must also be
        unique to limit the frequency of duplicate data:

            * :attr:`hostname`
            * :attr:`ip`
            * :attr:`subnet`
            * :attr:`port`

    """
    __tablename__ = TABLE_AGENT
    __table_args__ = (UniqueConstraint("hostname", "ip", "subnet", "port"), )
    STATE_ENUM = AgentState()
    STATE_DEFAULT = STATE_ENUM.ONLINE
    id = IDColumn()

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
    software = db.relation("AgentSoftwareModel", backref="agent",
                           lazy="dynamic")

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

        if key == "ip":
            valid = all([
                not ip.is_hostmask(), not ip.is_link_local(),
                not ip.is_loopback(), not ip.is_multicast(),
                not ip.is_netmask(), ip.is_private(),
                not ip.is_reserved()
            ])
            if not valid:
                raise ValueError("%s it not a private ip address" % value)

        elif key == "subnet":
            valid = all([
                not ip.is_hostmask(), not ip.is_link_local(),
                not ip.is_loopback(), not ip.is_multicast(),
                ip.is_netmask(), not ip.is_private(),
                ip.is_reserved()
            ])
            if not valid:
                raise ValueError("%s is not valid subnet" % value)

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