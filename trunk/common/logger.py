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

import os
import sys
import time
import types
import inspect
import tempfile
import logging.handlers # TODO: remove once you have switched to the below import
from logging import handlers

import preferences

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

# standard locations to log to
LOGS_STANDARD = os.path.join(tempfile.gettempdir(), 'pyfarm')
LOGS_JOBS = os.path.join(LOGS_STANDARD, 'logs')

# log to sys.stdout if requested
if os.getenv('PYFARM_STDOUT_LOGGING') != 'false' and preferences.LOGGING_STDOUT:
    log.startLogging(sys.stdout)

# setup standard log directories
# if they do not exist
for directory in (LOGS_STANDARD, LOGS_JOBS):
    if not os.path.isdir(directory):
        os.makedirs(directory)
        log.msg('created directory: %s' % directory)

def setLevel(level):
    '''sets the global logging level'''
    global LEVEL
    LEVEL = level
# end setLevel

# TODO: implement class below over the current setup
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
        level = eventDict.get('level') or\
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


def timestamp(fmt=None):
    '''
    returns a preformatted timestamp based on either an
    argument or preference
    '''
    fmt = fmt or preferences.LOGGING_TIMESTAMP
    return time.strftime(fmt)
# end timestamp

def openStream(path=None, mode='a', uuid=None, **environ):
    '''opens and returns a stream, performing a rollover if necessary'''
    stream = None
    if "LOGFILE" in environ:
        stream = open(environ.get('LOGFILE'), mode)

    elif preferences.LOGGING_ROLLOVER and path:
        log.msg("opening log %s" % path)
        handler = logging.handlers.RotatingFileHandler(
            path,
            mode=mode,
            maxBytes=preferences.LOGGING_ROLLOVER_SIZE,
            backupCount=preferences.LOGGING_ROLLOVER_COUNT
        )

        # attempt to rollover the log
        handler.doRollover()
        stream = handler.stream

    elif path:
        stream = open(path, mode)

    else:
        stream = tempfile.NamedTemporaryFile(
            dir=LOGS_JOBS, suffix=".log", delete=False,
            mode=mode
        )

    if stream and uuid:
        log.msg("opended log for %s at %s" % (uuid, stream.name))

    elif stream:
        log.msg("opened log %s" % stream.name)

    return stream
# end openStream

class LoggingBaseClass(object):
    '''Adds a custom self.log method to an inherited class'''
    def log(self, msg, **kwargs):
        module = inspect.getmodule(self.__class__).__name__
        classname = self.__class__.__name__
        kwargs['system'] = "%s.%s" % (module, classname)
        log.msg(msg, **kwargs)
    # end log
# end LoggingBaseClass

def startLogging(name, dest=None, mode='a'):
    '''
    Starts logging to a named file.

    :param string path:
        the path to store the log file, defaults to
        the temp directory path
    '''
    dest = dest or ''

    # do not log to file if the preferences
    # are setup not to
    if not preferences.LOGGING_FILE:
        return None

    # ensure the name has the standard convention
    if not name.endswith(preferences.LOGGING_EXTENSION):
        name += preferences.LOGGING_EXTENSION

    path = os.path.join(LOGS_STANDARD, dest, name)
    stream = openStream(path)
    log.startLogging(stream)
    return stream
# end startLogging


class Stream(object):
    '''
    contains methods for manipulation of a log stream

    :exception TypeError:
        raised if the provided object is not a stream

    :exception IOError:
        raised if the provided stream is already closed
    '''
    def __init__(self, stream):
        # ensure the provided object is a stream
        if not self.__stream(stream):
            raise TypeError("file stream required")

        # ensure the stream is not already closed
        if self.__closed(stream):
            raise IOError("stream is already closed")

        self.stream = stream
        self.name = self.stream.name
    # end __init__

    def __closed(self, stream):
        '''return True if the stream is already closed'''
        if hasattr(stream, 'close_called'):
            return stream.close_called

        else:
            return stream.closed
    # end __closed

    def __stream(self, stream):
        '''
        return True if the given object is a stream, False
        if it is not
        '''
        if isinstance(stream, file):
            return True

        elif hasattr(stream, 'file') and isinstance(stream.file, file):
            return True

        return False
    # end __stream

    def write(self, line, ending="\n"):
        '''writes a line to the file and flushes the stream'''
        self.stream.write(line+ending)
        self.stream.flush()
    # end write

    def close(self):
        '''closes the file stream'''
        self.stream.close()
    # end close

    def division(self, title, upper=True):
        '''writes a division to the log file'''
        if upper:
            title = title.upper()

        # create divisions
        length = preferences.LOGGING_DIVISION_LENGTH - 2
        length -= len(title)
        length = length / 2
        sep = preferences.LOGGING_DIVISION_SEP * length
        title = "%s %s %s" % (sep, title, sep)
        sep = preferences.LOGGING_DIVISION_SEP * len(title)

        # write results
        self.write(sep)
        self.write(title)
        self.write(sep)
    # end division

    def keywords(self, kwargs):
        '''writes keywords out to the file'''
        for key, value in kwargs.items():
            # normalize input data
            key = key.upper()
            value = str(value)

            self.write(preferences.LOGGING_KEYWORD_FORMAT % (key, value))
    # end keywords
# end Write

if __name__ == '__main__':
    log_streams = [None, "/tmp/test.log"]

    # sends log.msg to None and /tmp/test.log
    for stream in log_streams:
        observer = LogObserver(stream)
        observer.start()

    # example class logger
    class TestClass(LoggingBaseClass):
        def __init__(self):
            self.log("setting the test class")

    log.msg("test", level=logging.CRITICAL, system='HelloWorld')
    log.msg("test", level=logging.DEBUG, system='SomeSystem')
    test = TestClass()

    # Results:
    # 2012-03-02 00:47:40-0800 DEBUG [-] opened log PrintObserver
    # 2012-03-02 00:47:40-0800 DEBUG [-] opened log /tmp/test.log
    # 2012-03-02 00:47:40-0800 CRITICAL [HelloWorld] test
    # 2012-03-02 00:47:40-0800 DEBUG [SomeSystem] test
    # 2012-03-02 01:00:37-0800 [__main__.TestClass] setting the test class
