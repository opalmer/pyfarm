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


'''module for modifying network information in the database'''

from twisted.python import log

from pyfarm import errors
from pyfarm.db.tables import hosts
from pyfarm.db.transaction import Transaction
from pyfarm.db import utility

__all__ = ['host']

def host(hostname, **columns):
    '''
    :param string hostname:
        the hostname we are attempting to modify

    :param columns:
        columns to update for the given host

    :exception pyfarm.errors.HostNotFound:
        raised if t we could not find the host in

    :exception ValueError:
        raised if there was a problem with one or more of
        the input arguments
    '''
    if not columns:
        raise ValueError("no columns provided to update")

    utility.typecheck(hosts, columns)

    log.msg("preparing to modify '%s' database information" % hostname)
    with Transaction(hosts) as trans:
        filter = trans.query.filter(hosts.c.hostname == hostname)

        if not filter.count():
            raise errors.HostNotFound(hostname)

        elif filter.count() > 1:
            raise errors.MultipleHostsFound(hostname)

        for entry in filter:
            for key, value in columns.iteritems():
                # update the current entry
                current_value = getattr(entry, key)
                setattr(entry, key, value)
                args = (key, value, current_value)
                trans.log("setting %s to %s from %s" % args)
# end host

if __name__ == '__main__':
    host('mactastic.local', subnet='255.255.255.0')
