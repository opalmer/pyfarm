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

"""stores all custom errors which can be raised by pyfarm"""

from txjsonrpc import jsonrpclib

class Error(Exception):
    """base exception for all pyfarm errors"""
    def __init__(self, **kwargs):
        for key, value in kwargs.iteritems():
            setattr(self, key, value)
    # end __init__
# end Error


class DatabaseError(Error):
    """general database matching error which is meant to be subclassed"""
    def __str__(self):
        return self.msg
    # end __str__
# end DatabaseError


class NotFoundError(DatabaseError):
    """general not found error"""
    def __str__(self):
        args = (self.column_name, self.match_data, self.table)
        return "failed to find %s matching %s in %s" % args
        # end __str__
# end NotFoundError


class JobNotFound(NotFoundError):
    """raised if we failed to find the requested job in the database"""
    def __str__(self):
        return "job id %s does not exist" % self.id
    # end __str__
# end JobNotFound


class FrameNotFound(NotFoundError):
    """raised if we failed to find the requested frame in the database"""
    def __str__(self):
        return "frame id %s does not exist" % self.id
    # end __str__
# end FrameNotFound


class DuplicateEntry(DatabaseError):
    """general exception for duplicate entries"""
    def __str__(self):
        args = (self.column_name, self.match_data, self.table)
        return "found duplicate %s %s in %s" % args
    # end __str__
# end DuplicateEntry


class DuplicateHost(DatabaseError):
    """
    raised if we found more than one entry of the given name
    in the given table
    """
    def __str__(self):
        return "%s already exists in %s" % (self.match_data, self.table)
    # end __str__
# end DuplicateHosts


class HostNotFound(NotFoundError):
    """raised when we failed to requested find the host in the database"""
    def __str__(self):
        return "failed to find '%s' in the %s table" % (self.match_data, self.table)
    # end __str__
# end HostNotFound


class HostsOffline(DatabaseError):
    """
    raised if the table we are currently looking in does not have any
    hosts currently online
    """
    def __str__(self):
        return "failed to find any hosts online in the %s table" % self.table
    # end __repr__
# end HostsOffline


class JobTypeNotFoundError(Error):
    """raised if the requested jobtype could not be found"""
    def __str__(self):
        return "no such jobtype %s found in %s" % (self.jobtype, self.paths)
    # end __str__
# end JobTypeNotFoundError


class InsertionFailure(DatabaseError):
    """
    raised when either an insertion failed or when the results of the
    insertion are None
    """
    def __str__(self):
        return "insertion of %s into %s has failed" % (self.data, self.table)
    # end __str__
# end InsertionFailure


class ConfigurationError(Error):
    """basic error having to do with configuration problems"""
    def __str__(self):
        return self.msg
    # end __str__
# end ConfigurationError


class NetworkSetupError(ConfigurationError):
    """raised when there were problems setting up the network"""
    pass
# end NetworkSetupError


class InvalidDatabase(ConfigurationError):
    """
    database provided in the configuration is either unsupported or invalid
    """
    pass
# end InvalidDatabase


class RPCFault(jsonrpclib.Fault):
    """
    general fault raised by client and server applications
    operating over rpc
    """
    def __init__(self, code, message):
        jsonrpclib.Fault.__init__(self, code, message)
    # end __init__
# end RPCFault
