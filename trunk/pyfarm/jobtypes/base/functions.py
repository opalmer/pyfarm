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
import imp
import copy
import fnmatch
import socket
import inspect
import logging

from twisted.python import log

from pyfarm import logger, datatypes, fileio, prefs
from pyfarm.db import Transaction
from pyfarm.db.query import hosts
from pyfarm.db import tables

HOSTNAME = socket.getfqdn()
CWD = os.path.dirname(os.path.abspath(__file__))

__all__ = ['JobData', 'jobtypes', 'load', 'run', 'roots']

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

def roots():
    '''returns the root paths to search'''
    log.msg("searching for jobtype root directories")

    paths = []
    for root in prefs.get('jobtypes.search-paths'):
        # skip directories that do not exist
        if not os.path.isdir(root) and root not in paths:
            log.msg(
                "skipping %s, directory does not exist" % root
            )
            continue

        paths.append(root)

    return paths
# end roots

def __search():
    '''generator which returns a root and module name'''
    found = set()
    for root in roots():
        names = set()
        for filename in os.listdir(root):
            path = os.path.abspath(os.path.join(root, filename))

            # skip directories
            if os.path.isdir(path):
                continue

            # skip any files matching an exclusion pattern
            matches = False
            for pattern in prefs.get('jobtypes.excluded-names'):
                if not matches and fnmatch.fnmatch(filename, pattern):
                    matches = True

            if matches:
                continue

            modulename, extension = os.path.splitext(filename)

            # skip files which have already been found
            if modulename in found:
                continue
            else:
                found.add(modulename)
                yield root, modulename
# end __search

def __jobypes():
    '''generator which returns valid jobtype modules'''
    found = set()
    for root, modulename in __search():
        module = fileio.module.load(modulename, root)
        if hasattr(module, 'Job') and inspect.isclass(module.Job):
            yield module

        else:
            error = "jobtype '%s' does not have a Job " % modulename
            error += "class, skipping"
            log.msg(error, level=logging.WARNING)
# end __jobtypes

def jobtypes():
    '''returns a list of all valid jobtypes as strings'''
    types = set()

    for module in __jobypes():
        name = module.__name__
        types.add(name)

    return list(types)
# end jobtypes

def load(name):
    '''
    loads and returns a specific jobtype so long as it exists
    and contains the Job class

    :exception ImportError:
        raised if the a valid jobtype with the given
        name does not exist

    :return:
        return the jobtype's class object
    '''
    module = fileio.module.load(name, roots())

    try:
        return module.Job
    except AttributeError:
        log.msg(
            "%s does not contain the job class",
            level=logging.ERROR
        )
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
    with Transaction(tables.jobs, system="jobtypes.functions.run") as trans:
        trans.log("searching for jobtype for job %i" % jobid)
        query = trans.query.filter_by(id=frameid)
        result = query.first()
        jobtype = result.jobtype

    if jobtype is None:
        raise TypeError("failed to retrieve jobtype for job %i" % jobid)

    # load the jobtype class
    job = load(jobtype)

    # retrieve the job data and setup the
    # job object.
    data = JobData(jobid, frameid)
    jobtype = job(data)
    jobtype.run()
# end run
