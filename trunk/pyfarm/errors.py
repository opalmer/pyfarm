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


class HostNotFound(NameError):
    '''raised if the host was not found in database when we expected it to be'''
    def __init__(self, hostname):
        msg = "host %s was not found in the database" % hostname
        super(HostNotFound, self).__init__(msg)
    # end __init__
# end HostNotFound


class MultipleHostsFound(NameError):
    '''raised if multiple entries for a host are found where we expected one'''
    def __init__(self, hostname):
        msg = "found more than one entry for %s" % hostname
        super(MultipleHostsFound, self).__init__(msg)
    # end __init__
# end MultipleHostsFound


class NetworkSetupError(ValueError):
    '''raised when there were problems setting up the network'''
    pass
# end NetworkSetupError
