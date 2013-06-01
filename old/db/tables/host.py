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

"""
Host Tables
===========

Contains tables related to host management and relationship
declarations
"""

from sqlalchemy import Column, ForeignKey
from sqlalchemy.orm import relationship
from sqlalchemy.types import String, Integer

from pyfarm.logger import Logger

from pyfarm.db.tables._bases import NetworkHost
from pyfarm.db.tables import Base, Master, \
    TABLE_HOST, TABLE_HOST_GROUP, TABLE_MASTER, TABLE_HOST_SOFTWARE, \
    MAX_SOFTWARE_LENGTH, MAX_GROUP_LENGTH, ACTIVE_HOSTS_FRAME_STATES

logger = Logger(__name__)

class Host(Base, NetworkHost):
    """base host definition"""
    __tablename__ = TABLE_HOST
    repr_attrs = ("id", "masterid", "hostname", "running", "ip", "masterid")

    # column definitions
    _master = Column(Integer, ForeignKey('%s.id' % TABLE_MASTER))

    # relational definitions
    master = relationship('Master', uselist=False, backref="ref_host_master")
    software = relationship(
        'HostSoftware', uselist=True, backref="ref_host_host",
        primaryjoin='(HostSoftware._host == Host.id)'
    )
    groups = relationship('HostGroup', uselist=True, backref="ref_host_groups")
    running_frames = relationship(
        'Frame',
        primaryjoin='(Frame._host == Host.id) & '
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



class HostGroupingMixin(object):
    repr_attrs = ("name", "version")
    repr_attrs_skip_none = True

    # TODO: this must be declared differently for mixins
#    host = relationship(
#        'Host', uselist=False, backref="ref_hostsoftware_host",
#        primaryjoin='(Host.id == HostSoftware._host)'
#    )

    def __init__(self, name, host, version):
        self.name = name

        if version is not None:
            self.version = version

        if isinstance(host, Host):
            self._host = host.id
        else:
            self._host = host
    # end __init__

    # TODO: update to use relationship (see TODO above)
    @property
    def host(self):
        return self.session.query(Host).filter(
            Host.id == self._host
        ).one()
    # end host

    @property
    def hosts(self):
        """
        Finds all hosts which are using this software.  We do this using
        two queries so we retrieve only a unique lists of hosts
        """
        session = self.session
        all_entries = session.query(self.__class__).filter(
            self.__class__.name == self.name
        )
        return session.query(Host).filter(
            Host.id.in_(set( entry._host for entry in all_entries ))
        ).all()
    # end hosts
# end HostGroupingMixin


class HostSoftware(Base, HostGroupingMixin):
    """stores information about what software a host can run"""
    __tablename__ = TABLE_HOST_SOFTWARE

    _host = Column(Integer, ForeignKey(Host.id))
    name = Column(String(MAX_SOFTWARE_LENGTH), nullable=False)
    version = Column(String(MAX_SOFTWARE_LENGTH))

    def __init__(self, name, host, version=None):
        HostGroupingMixin.__init__(self, name, host, version)
    # end __init__
# end HostSoftware


class HostGroup(Base, HostGroupingMixin):
    """stores information about which group or groups a host belongs to"""
    __tablename__ = TABLE_HOST_GROUP

    # column definitions
    _host = Column(Integer, ForeignKey(Host.id))
    name = Column(String(MAX_GROUP_LENGTH), nullable=False)

    def __init__(self, name, host):
        HostGroupingMixin.__init__(self, name, host, None)
    # end __init__
# end HostGroup
