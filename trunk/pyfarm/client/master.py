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

'''module for dealing with storage and retrieval of the master'''

import socket
from twisted.python import log

from pyfarm import prefs

HOSTNAME = socket.gethostname()

def getmaster(options):
    '''primary function for retrieving the master'''
    count = 0
    if prefs.get('client.lookup-master'):
        options.lookup_master = True

    else:
        for value in (options.master, options.set_master, options.lookup_master):
            if value is not None:
                count += 1

    # ensure no more than a single master flag is used
    if count > 1:
        raise ValueError("only one flag can be used to set the master")

    elif options.master:
        log.msg("master '%s' provided by --master" % options.master)
        return options.master

    elif options.set_master:
        log.msg("master '%s' provided by --set-master" % options.set_master)
        setmaster(options.set_master)
        return options.set_master

    elif options.lookup_master:
        return retrievemaster()

    # if no flags have been given determine if we should
    # use the local preferences or lookup the master in the database
    elif not count:
        msg = "no command line flags or preferences overrides found, checking"
        msg += " master preference"
        log.msg(msg)

        master = prefs.get('client.master')

        # checks to see if the master has been set, if not then we need
        # to throw an error
        if not master:
            msg = "Master not set in preferences and no other methods of"
            msg += " master retrieval have been specified.  Please see the "
            msg += "command line flags or the 'lookup-master' preference."
            raise ValueError(msg)

        # if the master HAS been set then set the locally before returning
        # the results
        return master
# end getmaster

def setmaster(master):
    log.msg("setting master to '%s' in database" % master, level="NOT_IMPLEMENTED")
# end setmaster

def retrievemaster():
    log.msg("retrieving master for '%s'" % HOSTNAME, level="NOT_IMPLEMENTED")
# end retrievemaster