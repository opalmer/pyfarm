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

import datetime

from pyfarm import errors
from pyfarm.logger import Logger
from pyfarm.db.tables import hosts
from pyfarm.db.modify import base
from pyfarm.datatypes.network import HOSTNAME
from pyfarm.datatypes import system
from pyfarm.preferences import prefs

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
    return base.modify(
        hosts, 'hostname', hostname,
        exception_duplicate=errors.DuplicateHost,
        exception_notfound=errors.HostNotFound,
        **columns
    )
# end host


class UpdateMemory(Logger):
    '''
    Ensures that we do not attempt to update the ram
    entry for the current host more often than needed.
    '''
    def __init__(self):
        Logger.__init__(self, self)
        self.__lastupdate = None
        self.hostname = HOSTNAME
        self.timeout = prefs.get('host.ram-update-interval')
    # end __init__

    @property
    def lastupdate(self):
        '''property which returns the time since last update in seconds'''
        if self.__lastupdate is None:
            return self.timeout

        else:
            delta = datetime.datetime.now() - self.__lastupdate
            return delta.seconds
    # end lastupdate

    def shouldUpdate(self, force=False):
        '''returns True if we should update the database entry'''
        return force or self.lastupdate >= self.timeout
    # end shouldUpdate

    def update(self, force=False):
        '''
        updates the ram used in the database if the last update
        has exceeded or met the timeout criteria
        '''
        if self.shouldUpdate(force):
            # update the time that we made the last update
            # first just in case we encounter some excessive
            # slowness in the db update
            self.__lastupdate = datetime.datetime.now()

            # calculate the current resource usage
            ramuse = system.TOTAL_RAM - system.ram()
            swapuse = system.TOTAL_SWAP - system.swap()

            # log our current results
            args = (self.hostname, ramuse, swapuse)
            msg = "updating memory usage for %s (ram: %s, swap: %s)" % args
            self.info(msg)

            # finally, update the database
            try:
                host(self.hostname, ram_usage=ramuse, swap_usage=swapuse)

            except errors.HostNotFound:
                msg = "%s does not exist in the table, " % self.hostname
                msg += "cannot update memory"
                self.error(msg)
    # end update
# end UpdateMemory

update_memory = UpdateMemory()
