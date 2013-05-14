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

from sqlalchemy.orm import relationship

from pyfarm.db.tables import Base, TABLE_MASTER
from pyfarm.db.tables._bases import NetworkHost

class Master(Base, NetworkHost):
    __tablename__ = TABLE_MASTER
    repr_attrs = ("id", "hostname", "running", "ip")

    hosts = relationship(
        'Host', uselist=True, backref="ref_master_hosts",
        primaryjoin='Host._master == Master.id'
    )
    enabled_hosts = relationship(
        'Host', uselist=True, backref="ref_master_online_hosts",
        primaryjoin='(Host._master == Master.id) &'
                    '(Host.enabled == True)'
    )
    disabled_hosts = relationship(
        'Host', uselist=True, backref="ref_master_disabled_hosts",
        primaryjoin='(Host._master == Master.id) &'
                    '(Host.enabled == False)'
    )

    def __init__(self, hostname, ip, subnet, port=None, enabled=None):
        NetworkHost.__init__(self, hostname, ip, subnet, port, enabled)
    # end __init__
# end Master
