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
    def __init__(self, **kwargs):
        for key, value in kwargs.iteritems():
            setattr(self, key, value)
    # end __init__
# end Error


class DatabaseError(Error):
    '''general database matching error which is meant to be subclassed'''
    def __str__(self):
        return self.msg
    # end __str__
# end DatabaseError


class NotFoundError(DatabaseError):
    '''general not found error'''
    def __str__(self):
        args = (self.column_name, self.match_data, self.table)
        return "failed to find %s matching %s in %s" % args
        # end __str__
# end NotFoundError


class JobNotFound(NotFoundError):
    '''raised if we failed to find the requested job in the database'''
    def __str__(self):
        return "job id %s does not exist" % self.id
    # end __str__
# end JobNotFound


class FrameNotFound(NotFoundError):
    '''raised if we failed to find the requested frame in the database'''
    def __str__(self):
        return "frame id %s does not exist" % self.id
    # end __str__
# end FrameNotFound


class DuplicateEntry(DatabaseError):
    '''general exception for duplicate entries'''
    def __str__(self):
        args = (self.column_name, self.match_data, self.table)
        return "found duplicate %s %s in %s" % args
    # end __str__
# end DuplicateEntry


class DuplicateHost(DatabaseError):
    '''
    raised if we found more than one entry of the given name
    in the given table
    '''
    def __str__(self):
        return "%s already exists in %s" % (self.match_data, self.table)
    # end __str__
# end DuplicateHosts


class HostNotFound(NotFoundError):
    '''raised when we failed to requested find the host in the database'''
    def __str__(self):
        return "failed to find '%s' in the %s table" % (self.match_data, self.table)
    # end __str__
# end HostNotFound


class HostsOffline(DatabaseError):
    '''
    raised if the table we are currently looking in does not have any
    hosts currently online
    '''
    def __str__(self):
        return "failed to find any hosts online in the %s table" % self.table
    # end __repr__
# end HostsOffline


class JobTypeNotFoundError(Error):
    '''raised if the requested jobtype could not be found'''
    def __str__(self):
        return "no such jobtype %s found in %s" % (self.jobtype, self.paths)
    # end __str__
# end JobTypeNotFoundError


class InsertionFailure(DatabaseError):
    '''
    raised when either an insertion failed or when the results of the
    insertion are None
    '''
    def __str__(self):
        return "insertion of %s into %s has failed" % (self.data, self.table)
    # end __str__
# end InsertionFailure


class ConfigurationError(Error):
    '''basic error having to do with configuration problems'''
    def __str__(self):
        return self.msg
    # end __str__
# end ConfigurationError


class NetworkSetupError(ConfigurationError):
    '''raised when there were problems setting up the network'''
    pass
# end NetworkSetupError


class InvalidDatabase(ConfigurationError):
    '''
    database provided in the configuration is either unsupported or invalid
    '''
    pass
# end InvalidDatabase
