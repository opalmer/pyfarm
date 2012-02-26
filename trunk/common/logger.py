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
import tempfile
import logging.handlers

import preferences

from twisted.python import log

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
    stream = open("/tmp/test.log", 'w')
    write = Write(stream)
    write.division('hello world')
    #write.keywords(dict(os.environ))
