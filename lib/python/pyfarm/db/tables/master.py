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

from pyfarm.db.tables import Base, TABLE_MASTER
from pyfarm.db.tables._netbase import NetworkHost

class Master(Base, NetworkHost):
    __tablename__ = TABLE_MASTER
    repr_attrs = ("id", "hostname")

    def __init__(self, hostname, ip, subnet, port, enabled=None):
        NetworkHost.__init__(self, hostname, ip, subnet, port, enabled)
    # end __init__
# end Master
