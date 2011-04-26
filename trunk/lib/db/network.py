'''
HOMEPAGE: www.pyfarm.net
INITIAL: Dec 28 2010
PURPOSE: To create, edit, and remove network related database entries.

This file is part of PyFarm.
Copyright (C) 2008-2011 Oliver Palmer

PyFarm is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

PyFarm is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''
import os
import sys

from PyQt4 import QtSql

CWD      = os.path.dirname(os.path.abspath(__file__))
PYFARM   = os.path.abspath(os.path.join(CWD, "..", ".."))
if PYFARM not in sys.path: sys.path.append(PYFARM)

import includes
from lib import logger

logger = logger.Logger()
sql    = includes.connect()

def addHost(host, ip, sysinfo, status=0, fComplete=0, fFailed=0, fRendering=0):
    '''
    Add a host to the database and ensure it
    is only created once.  This function assumes
    the host you are adding is a "new" host.
    '''
    # if the new host is not in hosts, add it
    if not hostExists(host):
        query = QtSql.QSqlQuery(sql)
        s = "('%s', '%s', %i, %i, %i, %i)" % (host, ip, status, fComplete, fFailed, fRendering)
        query.exec_("INSERT INTO hosts VALUES %s" % s)
        return True

    else:
        logger.error("Cannot add host, %s is already in the database" % host)
        return False

def hostExists(host):
    '''Return true if the given host is in the database'''
    i     = 0
    hosts = []
    query = QtSql.QSqlQuery(sql)
    query.exec_("SELECT hostname FROM hosts")

    while query.next():
        hosts.append(query.value(i).toString())
        i += 1

    if host not in hosts:
        return False
    else:
        return True

def removeHost(host):
    '''Remove the requested host from the database'''
    if not hostExists(sql, host):
        logger.error("Cannot remove %s, it does not exist in the database" % sql)
        return False

    else:
        logger.debug("Removing %s from the database" % host)
        query = QtSql.QSqlQuery(sql)
        query.exec_("DELETE FROM hosts WHERE hostname = '%s'" % host)
        return True

if __name__ == '__main__':
    import time
    import string
    import random
    import includes

    logger.warning("Adding useless host information for testing!!")
    sql       = includes.connect(clean=True)
    MAX_HOSTS = 500
    start     = time.time()
    times     = []

    for i in range(MAX_HOSTS):
        # generate hostname
        hostname   = ''
        for i in range(random.randint(5, 8)):
            letter    = string.ascii_lowercase[random.randint(0, 25)]
            hostname += letter
        hostname += str(random.randint(1,60)).zfill(2)

        # generate ip
        ip = ['10']
        for i in range(3):
            ip.append(str(random.randint(1,128)))
        ip = '.'.join(ip)

        # generate other usefull status
        status    = random.randint(0, 6)
        complete  = random.randint(0, 1000)
        failed    = random.randint(0, 15)
        rendering = random.randint(0, 4)

        # add host to database
        tStart = time.time()
        addHost(sql, hostname, ip, status, complete, failed, rendering)
        times.append(time.time()-tStart)

    logger.debug("Total Time For %i Hosts: %fs" % (MAX_HOSTS, time.time()-start))

    # calculate the average
    average = sum(times)/len(times)
    logger.debug("Average Time Per Query: %fs" % average)
