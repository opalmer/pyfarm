# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2012 Oliver Palmer
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

from sqlalchemy import Column, ForeignKey
from sqlalchemy.orm import relationship
from sqlalchemy.types import String, Integer

from pyfarm.logger import Logger
from pyfarm.datatypes.enums import ACTIVE_HOSTS_FRAME_STATES
from pyfarm.db.tables._netbase import NetworkHost
from pyfarm.db.tables import Base, Master, \
    TABLE_HOST, TABLE_HOST_GROUP, TABLE_MASTER, TABLE_HOST_SOFTWARE, \
    MAX_SOFTWARE_LENGTH, MAX_GROUP_LENGTH

logger = Logger(__name__)

class Host(Base, NetworkHost):
    '''base host definition'''
    __tablename__ = TABLE_HOST
    repr_attrs = ("id", "masterid", "hostname", "running", "ip", "masterid")

    # column definitions
    _master = Column(Integer, ForeignKey('%s.id' % TABLE_MASTER))

    # relational definitions
    master = relationship('Master', uselist=False, backref="ref_host_master")
    software = relationship(
        'HostSoftware', uselist=True, backref="ref_host_host",
        primaryjoin='(HostSoftware.host == Host.id)'
    )
    groups = relationship('HostGroup', uselist=True, backref="ref_host_groups")
    running_frames = relationship(
        'Frame',
        primaryjoin='(Frame.hostid == Host.id) & '
                    '(Frame.state.in_(%s))' % (ACTIVE_HOSTS_FRAME_STATES, )
    )

    def __init__(self, hostname, ip, subnet, port=None, enabled=None, master=None):
        NetworkHost.__init__(self, hostname, ip, subnet, port, enabled)

        if master is not None:
            if isinstance(master, Master):
                self._master = master.id
            else:
                self._master = master
    # end __init__
# end Host


class HostSoftware(Base):
    '''stores information about what software a host can run'''
    __tablename__ = TABLE_HOST_SOFTWARE
    repr_attrs = ("host", "name")

    # column definitions
    host = Column(Integer, ForeignKey(Host.id))
    name = Column(String(MAX_SOFTWARE_LENGTH), nullable=False)
    hosts = relationship(
        'Host', uselist=True, backref="ref_hostsoftware_hosts",
        primaryjoin='(Host.id == HostSoftware.host)'
    )

    def __init__(self, host, name):
        if isinstance(host, Host):
            self.host = host.id
        else:
            self.host = host

        self.name = name
    # end __init__
# end HostSoftware


class HostGroup(Base):
    '''stores information about which group or groups a host belongs to'''
    __tablename__ = TABLE_HOST_GROUP
    repr_attrs = ("host", "name")

    # column definitions
    host = Column(Integer, ForeignKey(Host.id), nullable=False)
    name = Column(String(MAX_GROUP_LENGTH), nullable=False)
    hosts = relationship('Host', uselist=True, backref=__tablename__)

    def __init__(self, host, name):
        self.host = host
        self.name = name
    # end __init__
# end HostGroup

