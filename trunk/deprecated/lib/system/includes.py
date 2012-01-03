# No shebang line, this module is meant to be imported
#
# INITIAL: May 28 2010
# PURPOSE: To query and return information about the local system
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

import os
import sys
import site
import fnmatch

from PyQt4 import QtCore

cwd = os.path.dirname(os.path.abspath(__file__))
root = os.path.abspath(os.path.join(cwd, "..", ".."))
site.addsitedir(root)

def SimpleCommand(cmd, all=False, debug=False):
    '''
    By default this function will return the first results only
    from the request command.  Enabling all however will return
    a complete list.
    '''
    from lib import logger
    process = QtCore.QProcess()

    if debug:
        log = logger.Logger("Utility.RunCommand", LOGLEVEL)

    # start process and wait for it complete
    process.start(cmd)

    if debug:
        logger.debug("Starting process PID: %i" % process.pid())

    if not process.waitForStarted(): return False
    if not process.waitForFinished(): return False

    if debug:
        logger.debug("Process Complete")

    results = process.readAll().data()

    if all: return results
    else:   return results.split(os.linesep)[0]


def clean(option=None, opt=None, value=None, parser=None):
    '''Remove all pyc files (and any other tmp files'''
    for dirpath, dirnames, files in os.walk(PYFARM):
        if not fnmatch.fnmatch(dirpath, "*.git*"):
            for dirname in dirnames:
                if not fnmatch.fnmatch(dirname, "*.git*"):
                    for filename in files:
                        path = os.path.join(dirpath, dirname, filename)
                        if path.endswith(".pyc") and os.path.isfile(path):
                            os.remove(path)
    logger.info("Lite Cleanup Complete")

def cleanAll(option=None, opt=None, value=None, parser=None):
    '''Cleanup all files including pyc, database, and lock file'''
    logger.fixme("Custom database location is NOT supported")
    logger.fixme("Cannot remove lock file due to circular dependency")
    clean('','','','')

    db = os.path.join(PYFARM, "PyFarmDB.sql")
    if os.path.isfile(db):
        os.remove(db)

    logger.info("Full Cleanup Complete")
