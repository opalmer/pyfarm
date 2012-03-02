#!/usr/bin/env python
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

'''simple tests'''

import os
import sys
import types
import logging
from logging import handlers

from common import logger
from twisted.python import log

from twisted.python import log

# cutoff for logging levels
LEVEL = logging.DEBUG

# format for all log statements
FORMAT = "%(level)s [%(system)s] %(text)s\n"

# how we should format unknown levels
UNKNOWN_LEVEL_FORMAT = "Level %i"

# a complete list of open streams (by name)
STREAMS = set()

# Custom log level names and their associated level integer
# this allows custom levels to be added by modules or classes
# Example (note case on key):
#   DATABASE : 10
CUSTOM_LEVELS = {}

def setLevel(level):
    '''sets the global logging level'''
    global LEVEL
    LEVEL = level
# end setLevel

class LogObserver(log.FileLogObserver):
    '''
    Logging observer for sys.stdout and sys.stderr

    :param None or string or types.FileType stream:
        The path or stream to log to.  If the argument provided
        is a string then we will attempt to create a log file
        at the given location.  In the event the provided 'stream'
        is actually None then send everything out via print.

    :param string mode:
        the mode to open stream in (only applies to file streams)

    :param integer backups:
        number of backups to keep of logfiles

    :param string name:
        name of this observer

    :exception TypeError:
        raised if the provided or resolved stream argument is
        not a file stream
    '''
    def __init__(self, stream, mode='a', backups=10):
        if isinstance(stream, types.StringType):
            path = os.path.abspath(stream)
            dirname = os.path.dirname(path)

            # if the directory does not exist create it
            if not os.path.isdir(dirname):
                os.makedirs(dirname)

            # if the file already exists and the mode
            # is append then rollover the file on disk
            if os.path.isfile(path) and mode == 'a':
                rotate = handlers.RotatingFileHandler(path, backupCount=backups)
                rotate.doRollover()

            stream = open(path, mode)

        # attributes for external use
        self.stream = stream
        if stream is not None:
            if not isinstance(stream, types.FileType):
                raise TypeError("provided argument is not a file stream or ")

            self.name = self.stream.name
            log.FileLogObserver.__init__(self, stream)

        else:
            self.name = 'PrintObserver'
    # end __init__

    def emit(self, eventDict):
        text = log.textFromEventDict(eventDict)

        if text is None:
            return

        # retrieve the logging string
        level = eventDict.get('level') or \
                eventDict.get('logLevel') or \
                logging.DEBUG
        integer = isinstance(level, types.IntType)

        # determine if we should even emit the given log level
        if integer and level < LEVEL:
            return

        elif isinstance(level, types.StringTypes):
            level = level.upper()
            if level in CUSTOM_LEVELS and CUSTOM_LEVELS.get(level) < LEVEL:
                return

        # if level is an integer then we need to either
        # retrieve the name or construct one
        if integer and level in logging._levelNames:
            level = logging.getLevelName(level)

        elif integer:
            level = UNKNOWN_LEVEL_FORMAT % level

        timeStr = self.formatTime(eventDict['time'])
        fmtDict = {
            'system': eventDict['system'],
            'text': text.replace("\n", "\n\t"),
            'level' : level.upper()
        }
        msg = timeStr + " " + log._safeFormat(FORMAT, fmtDict)

        if self.stream is not None:
            log.util.untilConcludes(self.write, msg)
            log.util.untilConcludes(self.flush)

        else:
            print msg.strip()
    # end emit

    def start(self):
        '''Start observing log events'''
        log.addObserver(self.emit)
        log.msg("opened log %s" % self.name)

        # add stream to global streams
        global STREAMS
        if self.name not in STREAMS:
            STREAMS.add(self.name)
    # end start

    def stop(self):
        '''Stop observing log events'''
        log.removeObserver(self.emit)
        log.msg("closed log %s" % self.name)

        global STREAMS
        if self.name in STREAMS:
            STREAMS.remove(self.name)
    # end stop
# end LogObserver

#observer = PrintObserver()
#observer.start()
log_streams = [None, "/tmp/test.log"]
for stream in log_streams:
    observer = LogObserver(stream)
    observer.start()

class TestLogClass(logger.LoggingBaseClass):
    def __init__(self):
        self.log("setup test class")

log.msg("test", level=logging.CRITICAL, system='HelloWorld')
log.msg("test", level=logging.DEBUG, system='HelloWorld')

