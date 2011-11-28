# No shebang line, this module is meant to be imported
#
# INITIAL: Nov 15 2011
# PURPOSE: To provide a central location for log setup
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

'''
This module performs several functions including initial setup of log
directories and process specific logging.
'''

import os
import sys
import time
import socket
import tempfile
from twisted.python import log

ENDLINE = os.linesep
LOG_ROOT = os.path.join(tempfile.gettempdir(), "pyfarm", "client", "logs")
CLIENT_LOG = os.path.join(os.path.dirname(LOG_ROOT), "client-log.log")
CLIENT_LOG_STREAM = open(CLIENT_LOG, 'a')

# TODO: add rotating file handler
# add a break to the client log stream so we don't confuse
# multiple client start/stops
CLIENT_LOG_STREAM.write(
    "------ Starting Stream %s------%s" % (time.strftime("%D %T"), os.linesep)
)

# Contains a dictionary of log files based on UUID, mappings will be
# maintained so long as the client is running
LOG_HANDLERS = {}

log.startLogging(sys.stdout)
log.startLogging(CLIENT_LOG_STREAM)

# create the global log directory if
# it does not exist
if not os.path.isdir(LOG_ROOT):
    os.makedirs(LOG_ROOT)
    log.msg("created log directory")

log.msg("log directory: %s" % LOG_ROOT)
log.msg("client log: %s" % CLIENT_LOG)

def writeLine(log, line, flush=True, endline=None):
    '''
    Writes a line of text to the given log file

    :param stream log:
        the log file stream to write to

    :param string line:
        the line of text to write

    :param string endline:
        the endline to use instead of ENDLINE
    '''
    log.write(line+(endline or ENDLINE))

    if flush:
        log.flush()
# end writeLine

def writeHeader(log, **headerKeywords):
    '''
    Writes a header to the log file, arguments passed as keywords will
    recieve their own line.
    '''
    timestamp = time.strftime("%D %T")
    hostname = socket.gethostname()

    writeLine(log, "Log Opened: %s" % timestamp)
    writeLine(log, "Hostname: %s" % hostname)

    for key, value in headerKeywords.items():
        writeLine(log, "%s: %s" % (key, value))
# end writeHeader

def openLog(uuid, **headerKeywords):
    '''
    Open or return a log file for the given uuid.

    :param uuid.UUID uuid:
        uuid object to create or open a log file for

    :param string comment:
        optional comment to add to header of log

    :param string command:
        optional command to add to log
    '''
    if uuid not in LOG_HANDLERS:
        # create the log file
        log = LOG_HANDLERS[uuid] = tempfile.NamedTemporaryFile(
                                    dir=LOG_ROOT,
                                    suffix=".log", delete=False
                                   )
        writeHeader(log)

    return LOG_HANDLERS[uuid]
# end openLog
