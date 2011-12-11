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

from __future__ import with_statement

import os
import sys
import time
import uuid
import socket
import tempfile
from twisted.python import log

import preferences

ENDLINE = os.linesep
LOG_ROOT = os.path.join(tempfile.gettempdir(), "pyfarm", "client", "logs")

def timestamp():
    '''read the timestamp format from preferences and return a value'''
    return time.strftime(preferences.TIMESTAMP)
# end timestamp

def writeLine(log, line, endline=None):
    '''
    Writes a line of text to the given log file

    :param stream log:
        the log file stream to write to

    :param string line:
        the line of text to write

    :param string endline:
        the endline to use instead of ENDLINE

    :exception UnknownLog:
        raised if the uuid in place of log does not exist in LOG_HANDLERS

    :exception AttributeError:
        raised if log does not have the required write function
    '''
    # if we are being passed a uuid then retrieve
    # the file based on the uuid (if it exists)
    if isinstance(log, uuid.UUID):
        if not LOG_HANDLERS.has_key(log):
            raise UnknownLog(log)
        log = LOG_HANDLERS[log]

    log.write(line+(endline or ENDLINE))
    log.flush()
# end writeLine

def openLog(uid, **environ):
    '''
    Open or return a log file for the given uuid.

    :param uuid.UUID uuid:
        uuid object to create or open a log file for
    '''
    path = None
    if environ.get('LOGFILE'):
       path = open(environ.get('LOGFILE'), 'w')

    stream = path or tempfile.NamedTemporaryFile(
                dir=LOG_ROOT, suffix=".log", delete=False
            )
    log.msg("creating log for %s at %s" % (str(uid), stream.name))
    return stream
# end openLog

def writeMessage(log, msg):
    '''writes a message to the log file'''
    spacer = "="*15
    msg = "%s %s %s" % (spacer, msg, spacer)
    header = "="*len(msg)
    writeLine(log, header)
    writeLine(log, msg)
    writeLine(log, header)
# end writeMessage

def writeKeywords(log, keywords):
    '''writes keywords from a dictionary to the log file'''
    for key, value in keywords.items():
        writeLine(log, "%s: %s" % (key, value))
# end writeKeywords

def writeHeader(log, keywords):
    '''
    Writes a header to the log file, arguments passed as keywords will
    receive their own line.
    '''
    writeKeywords(log, keywords)
    writeMessage(log, "BEGIN PROCESS")
# end writeHeader

def writeFooter(log, keywords):
    '''
    Writes a footer to the end of the log file, arguments passed as keywords will
    receive their own line.
    '''
    writeMessage(log, "END PROCESS")
    writeKeywords(log, keywords)
# end writeFooter

##
## base logging setup past this point
##

if not os.path.isdir(LOG_ROOT):
    os.makedirs(LOG_ROOT)

# client logging to standard out to sys.stdout logging
if preferences.CLIENT_LOG_STDOUT:
    log.startLogging(sys.stdout)

# client standard out to file logging
if preferences.CLIENT_LOG_FILE:
    CLIENT_LOG = os.path.join(os.path.dirname(LOG_ROOT), "client-log.log")
    CLIENT_LOG_STREAM = open(CLIENT_LOG, 'a')

    # TODO: add rotating file handler
    # add a break to the client log stream so we don't confuse
    # multiple client start/stops
    CLIENT_LOG_STREAM.write(
    "------ Starting Stream %s------%s" % (timestamp(), os.linesep)
    )

    log.startLogging(CLIENT_LOG_STREAM)

# if client loggint to file was not selected then
# set the relevant variables to None
else:
    CLIENT_LOG = None
    CLIENT_LOG_STREAM = None

# create the global log directory if
# it does not exist
if not os.path.isdir(LOG_ROOT):
    os.makedirs(LOG_ROOT)
    log.msg("created log directory")

log.msg("client log: %s" % CLIENT_LOG)
log.msg("job log directory: %s" % LOG_ROOT)
