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

import logging
import sqlalchemy as sql
from sqlalchemy.orm import sessionmaker
from twisted.python import log
from twisted.internet import reactor

import preferences

# create and connect to the engine
engine = sql.create_engine(preferences.DB_URL)
engine.echo = preferences.DB_ECHO

# create global metadata object and bind the engine
metadata = sql.MetaData()
metadata.bind = engine

# TODO: move table mapping to common.db so all objects can use the same mapper
# TODO: move table mapping to common.db so all objects can use the same mapper
# TODO: move table mapping to common.db so all objects can use the same mapper
# TODO: move table mapping to common.db so all objects can use the same mapper

def updateHostInfo(client):
    '''
    updates relevant host information in the database
    such as ram and swap usage

    :param client.Client client:
        provides access to the client methods and attributes for the
        purposes of updating the database with resource information
    '''
    if not client.xmlrpc_master():
        log.msg("master not set, cannot update resources table")
        return

    # added so we can still provide up to date information
    # for sqlite databases
    if preferences.DB_ENGINE == "sqlite":
        msg = "remote host information update is not supported with sqlite"
        log.msg(msg, logLevel=logging.WARNING)

    # if DB_ALLOW_REMOTE is False or the engine is sqlite then
    # perform the update via xmlrpc
    if not preferences.DB_ALLOW_REMOTE or preferences.DB_ENGINE == "sqlite":
        log.msg("using xmlrpc to update host information")
        return

    # otherwise connect to the database
    try:
        hosts = sql.Table('pyfarm_hosts', metadata, autoload=True)

    except sql.exc.NoSuchTableError, error:
        log.msg("no such table %s" % error, logLevel=logging.ERROR)
        return

    else:
        log.msg("attempting to update host information in database")

    Session = sessionmaker(bind=engine)
    session = Session()

    host = hosts.select(hosts.c.hostname == client.net.xmlrpc_hostname())
    print "============",dir(host)
    host.swap_usage = 999999999
    session.flush()

#    host.execute({"swap_usage" : 999999})
#    host.in
#    host.commit()

#    host.u

# end updateHostInfo

