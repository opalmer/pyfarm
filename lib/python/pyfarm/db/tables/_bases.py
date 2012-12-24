# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2013 Oliver Palmer
#
# PyFarm is free software: you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# PyFarm is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.

'''
contains the main base classes used by other tables
'''

import socket
from datetime import datetime
from sqlalchemy import Column, event
from sqlalchemy.orm import validates
from sqlalchemy.types import String, Boolean, Integer, DateTime

from pyfarm.net import openport
from pyfarm.logger import Logger
from pyfarm.datatypes.enums import State
from pyfarm.db.tables import MAX_HOSTNAME_LENGTH, MAX_IPV4_LENGTH, \
    MIN_PORT, MAX_PORT, DEFAULT_PRIORITY

logger = Logger(__name__)

class NetworkHost(object):
    '''mixin which defines common attributes that all network nodes have'''
    hostname = Column(String(MAX_HOSTNAME_LENGTH), nullable=False, unique=True)
    ip = Column(String(MAX_IPV4_LENGTH), nullable=False, unique=True)
    subnet = Column(String(MAX_IPV4_LENGTH), nullable=False)
    port = Column(Integer, nullable=False)
    enabled = Column(Boolean, default=True)

    def __init__(self, hostname, ip, subnet, port, enabled=False):
        self.hostname = hostname
        self.ip = ip
        self.subnet = subnet

        # autoselect if an integer was not provided for the port
        if not isinstance(port, int):
            port = openport()

        self.port = port

        if enabled is not None:
            self.enabled = enabled
    # end __init__

    @validates('port')
    def validate_port(self, key, port):
        if port not in xrange(MIN_PORT, MAX_PORT+1):
            raise ValueError("port must be in range %s-%s" % (MIN_PORT, MAX_PORT))

        return port
    # end validate_port

    @validates('ip', 'subnet')
    def validate_address(self, key, ip):
        # TODO: IPv6 support
        try:
            socket.inet_aton(ip)

        except socket.error:
            raise ValueError("'%s' is not a valid %s address" % (ip, key))

        # inet_aton does not catch problems with addresses that have invalid
        # length to begin with
        length = len([ i for i in ip.split(".") if i.strip() ])
        if length != 4:
            msg = "invalid length for IPv4 address, "
            msg += "expected 4 groups but found %s" % length
            raise ValueError(msg)

        return ip
    # end validate_address
# end HostBase


class TaskBase(object):
    '''base of task based tables such as jobs and frames'''
    # state, requeue, and priority
    state = Column(Integer, default=State.QUEUED)
    priority = Column(Integer, default=DEFAULT_PRIORITY)
    attempts = Column(Integer, default=0)

    # time tracking
    time_submitted = Column(DateTime, default=datetime.now)
    time_started = Column(DateTime)
    time_finished = Column(DateTime)

    def __init__(self, state, priority):
        if state is not None:
            self.state = state

        if priority is not None:
            self.priority = priority
    # end __init__

    @validates('state')
    def validate_state(self, key, state):
        if state not in State:
            state_names = [ State.get(state) for state in State ]
            raise ValueError("%s must be in %s" % (key, state_names))

        return state
    # end validate_state

    @property
    def elapsed(self):
        '''returns the time elapsed since the task has started'''
        started = self.time_started
        finished = self.time_finished

        # raise exception if the task has not started
        # yet
        if started is None:
            raise ValueError("%s has not started yet" % self)

        if finished is None:
            finished = datetime.now()

        delta = finished - started
        return delta.days * 86400 + delta.seconds
    # end elapsed
# end TaskBase

