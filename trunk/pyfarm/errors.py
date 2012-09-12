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

'''stores all custom errors which can be raised by pyfarm'''

class Error(Exception):
    '''base exception for all pyfarm errors'''
    pass
# end Error


class HostNotFound(Error):
    '''raised when we failed to requested find the host in the database'''
    def __init__(self, hostname, table):
        self.hostname = hostname
        self.table = table
        super(HostNotFound, self).__init__()
    # end __init__

    def __str__(self):
        return "failed to find '%s' in the %s table" % (self.hostname, self.table)
    # end __str__
# end HostNotFound


class DuplicateHost(Error):
    '''
    raised if we found more than one entry of the given name
    in the given table
    '''
    def __init__(self, hostname, table):
        self.hostname = hostname
        self.table = table
        super(DuplicateHost, self).__init__()
    # end __init__

    def __str__(self):
        return "%s already exists in %s" % (self.hostname, self.table)
    # end __str__
# end DuplicateHosts


class DuplicateEntry(Error):
    '''general exception for duplicate entries'''
    def __init__(self, match_name, entry, table):
        self.match_name = match_name
        self.entry = entry
        self.table = table
        super(DuplicateEntry, self).__init__()
    # end __init__

    def __str__(self):
        args = (self.match_name, self.entry, self.table)
        return "found duplicate %s %s in %s" % args
    # end __str__
# end DuplicateEntry


class NetworkSetupError(Error):
    '''raised when there were problems setting up the network'''
    pass
# end NetworkSetupError


class InvalidDatabase(Error):
    '''
    database provided in the configuration is either unsupported or invalid
    '''
    pass
# end InvalidDatabase
