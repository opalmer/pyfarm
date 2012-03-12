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

'''
functions for jobtype system related queries and setup
'''

from __future__ import with_statement

import os
import copy
import socket
import inspect
import logging

from twisted.python import log

from common import logger, datatypes
from common.preferences import prefs
from common.db import Transaction, tables
from common.db.query import hosts

HOSTNAME = socket.getfqdn()
SKIP_MODULES = prefs.get('jobtypes.excluded-names')
CWD = os.path.dirname(os.path.abspath(__file__))

__all__ = ['JobData', 'jobtypes', 'load']

class JobData(logger.LoggingBaseClass):
    '''
    base object used by jobtypes which provides information about a
    job and frame from the database

    :exception TypeError:
        raised if the job or frame do not exist in the database
    '''
    TABLES = {
        "frame" : tables.frames,
        "job" : tables.jobs
    }

    def __init__(self, jobid, frameid):
        self.job = self.__lookup("job", jobid)
        self.frame = self.__lookup("frame", frameid)
    # end __init__

    def __lookup(self, name, id):
        table = self.TABLES[name]
        result = None
        hostid = None
        self.log("looking up %s (id: %i) in %s" % (name, id, table))

        # get the hostid if we are about to work
        # with the frames table
        if name == "frame":
            hostid = hosts.hostid(HOSTNAME)

        with Transaction(table) as trans:
            query = trans.query.filter_by(id=id)
            result = query.first()

            # ensure entry exists in the database
            if result is None:
                args = (name, id, table)
                raise TypeError("%s entry (id: %i) does not exist in %s" % args)

            # setup the hostname and mark the frame as
            # running
            if name == "frame":
                result.host = hostid
                result.state = datatypes.State.RUNNING

        return copy.deepcopy(result)
    # end __lookup
# end JobData

def jobtypes():
    '''returns a list of all valid jobtypes as strings'''
    types = set()

    for listing in os.listdir(CWD):
        filename, extension = os.path.splitext(listing)
        if filename not in SKIP_MODULES:
            # load the module and ensure it has an attribute 'Job'
            # that is a class
            module = __import__(filename, locals(), globals())
            if hasattr(module, 'Job') and inspect.isclass(module.Job):
                types.add(filename)

            else:
                log.msg(
                    "'Job' is not a class in %s" % module.__file__,
                    level=logging.WARNING,
                    system="jobtypes.functions.jobtypes"
                )

    return list(types)
# end jobtypes

def load(name):
    '''
    loads and returns a specific jobtype so long as it exists
    and contains the Job class

    :exception NameError:
        raised if the a valid jobtype with the given
        name does not exist

    :return:
        return the jobtype's class object
    '''
    if name not in jobtypes():
        raise NameError("no such jobtype %s" % name)

    log.msg("loading jobtype %s" % name)
    module = __import__(name, locals(), globals(), fromlist=['jobtypes'])
    return module.Job
# end load

def run(jobid, frameid):
    '''
    Given a job id and frame id load the proper jobtype.  Generally this
    function should not be called directory and instead should
    be wrapped other code to handle log setup and exceptions:

        try:
            <setup log >
            run(<jobid>, <frameid>)

        except Exception, error:
            <mark job as failed>
            <end error to logs>
    '''
    jobtype = None

    # retrieve the jobtype
    with Transaction(tables.jobs) as trans:
        query = trans.query.filter_by(id=frameid)
        result = query.first()
        jobtype = result.jobtype

    if jobtype is None:
        raise TypeError("failed to retrieve job type")

    # load the jobtype class
    job = load(jobtype)

    # retrieve the job data and setup the
    data = JobData(jobid, frameid)
    instance = job(data)
    instance.run()
# end run
