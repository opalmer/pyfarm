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

from sqlalchemy.orm import validates

from pyfarm.flaskapp import db
from pyfarm.config.enum import HostState
from pyfarm.models.mixins import StateValidationMixin, RandIdMixin
from pyfarm.models.constants import (MAX_HOSTNAME_LENGTH,
    MAX_IPV4_LENGTH, REGEX_IPV4, REGEX_HOSTNAME, TABLE_AGENT, TABLE_AGENT_TAGS)


class AgentTags(db.Model):
    __tablename__ = TABLE_AGENT_TAGS
    _agentid = db.Column(db.Integer, db.ForeignKey("%s.id" % TABLE_AGENT), primary_key=True)
    tag = db.Column(db.String)


class Agent(db.Model, RandIdMixin, StateValidationMixin):
    """Information related to the agent"""
    __tablename__ = TABLE_AGENT
    STATE_ENUM = HostState()
    STATE_DEFAULT = STATE_ENUM.ONLINE

    # columns
    enabled = db.Column(db.Boolean, default=True)
    state = db.Column(db.Integer, nullable=False)
    hostname = db.Column(db.String(MAX_HOSTNAME_LENGTH), nullable=False)
    ip = db.Column(db.String(MAX_IPV4_LENGTH), nullable=False)
    subnet = db.Column(db.String(MAX_IPV4_LENGTH), nullable=False)

    # relationships
    tasks = db.relationship("Task", backref="agent", lazy="dynamic")
    tags = db.relationship("AgentTags", backref="agent", lazy="dynamic")

    @validates("hostname")
    def validate_hostname(self, key, value):
        if not REGEX_HOSTNAME.match(value):
            raise ValueError("%s is not valid for %s" % (value, key))

        return value

    @validates("ip", "subnet")
    def validate_address(self, key, value):
        if not REGEX_IPV4.match(value):
            raise ValueError("%s is not valid for %s" % (value, key))

        return value