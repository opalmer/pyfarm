# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2011 Oliver Palmer
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
import tempfile
import logging.handlers

import preferences

from twisted.python import log

LOGS_STANDARD = os.path.join(tempfile.gettempdir(), 'pyfarm')
LOGS_JOBS = os.path.join(LOGS_STANDARD, 'logs')

# log to sys.stdout if requested
if preferences.LOGGING_STDOUT:
    log.startLogging(sys.stdout)

# setup standard log directories
# if they do not exist
for directory in (LOGS_STANDARD, LOGS_JOBS):
    if not os.path.isdir(directory):
        os.makedirs(directory)
        log.msg('created directory: %s' % directory)

def openStream(path, mode='a'):
    '''opens and returns a stream, performing a rollover if necessary'''
    if preferences.LOGGING_ROLLOVER:
        handler = logging.handlers.RotatingFileHandler(
            path,
            mode=mode,
            maxBytes=preferences.LOGGING_ROLLOVER_SIZE,
            backupCount=preferences.LOGGING_ROLLOVER_COUNT
        )

        # attempt to rollover the log
        handler.doRollover()
        return handler.stream

    else:
        return open(path, mode)
# end openStream

def startLogging(name, mode='a'):
    '''
    Starts logging to a named file.

    :param string path:
        the path to store the log file, defaults to
        the temp directory path

    '''
    # do not log to file if the preferences
    # are setup not to
    if not preferences.LOGGING_FILE:
        return None

    # ensure the name has the standard convention
    if not name.endswith(preferences.LOGGING_EXTENSION):
        name += preferences.LOGGING_EXTENSION

    path = os.path.join(LOGS_STANDARD, name)
    stream = openStream(path)
    log.startLogging(stream)
# end startLogging
