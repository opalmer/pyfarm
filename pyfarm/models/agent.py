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
from pyfarm.config.enum import HostState, OperatingSystem
from pyfarm.models.mixins import StateMixin, RandIdMixin
from pyfarm.models.constants import (MAX_HOSTNAME_LENGTH,
    MAX_IPV4_LENGTH, REGEX_IPV4, REGEX_HOSTNAME, TABLE_AGENT,
    MIN_RAM_MB, MAX_RAM_MB, MIN_AGENT_PORT, MAX_AGENT_PORT)


class Agent(db.Model, RandIdMixin, StateMixin):
    __tablename__ = TABLE_AGENT
    STATE_ENUM = HostState()
    OS_ENUM = OperatingSystem()

    # columns
    enabled = db.Column(db.Boolean, default=True)
    hostname = db.Column(db.String(MAX_HOSTNAME_LENGTH), nullable=False)
    ip = db.Column(db.String(MAX_IPV4_LENGTH), nullable=False)
    port = db.Column(db.Integer, nullable=False)
    subnet = db.Column(db.String(MAX_IPV4_LENGTH), nullable=False)
    cpus = db.Column(db.Integer, nullable=False)
    ram = db.Column(db.Integer, nullable=False)
    os = db.Column(db.Integer, nullable=False)

    # relationships
    tasks = db.relationship("Task", backref="agent", lazy="dynamic")
    groups = db.relationship("Group", backref="group", lazy="dynamic")

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

    @validates("cpus")
    def validate_cpus(self, key, value):
        if value < 1:
            raise ValueError("at least one cpu is required")

        return value

    @validates("ram")
    def validate_ram(self, key, value):
        if value < MIN_RAM_MB or value > MAX_RAM_MB:
            args = (MIN_RAM_MB, MAX_RAM_MB)
            raise ValueError("ram must be between %s and %s megabytes" % args)

        return value

    @validates("os")
    def validates_os(self, key, value):
        if value not in self.OS_ENUM:
            msg = "`%s` is not a valid operating system, " % value
            msg += "valid systems are %s" % self.OS_ENUM.values()
            raise ValueError(msg)

        return value

    @validates("port")
    def validates_port(self, key, value):
        if value < MIN_AGENT_PORT or value > MAX_AGENT_PORT:
            args = (MIN_AGENT_PORT, MAX_AGENT_PORT)
            msg = "port number must be between %s and %s" % args
            raise ValueError(msg)

        return value