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
General utility functions that are not specific to individual components
of PyFarm.
'''

import os
import datetime
from itertools import ifilter, imap
from os.path import exists, expanduser, expandvars

class ScheduledRun:
    # old style class since twisted classes are also old style
    '''
    Basic class which informs child classes if they should
    perform their indicated function
    '''
    def __init__(self, timeout):
        self.timeout = timeout
        self.lastrun = None
    # end __init__

    @property
    def lastupdate(self):
        '''
        returns the time since last update or the timeout itself
        if lastrun has not been set
        '''
        if self.lastrun is None:
            return self.timeout

        else:
            delta = datetime.datetime.now() - self.lastrun
            return delta.seconds + 1 # accounts for most inaccuracies in time calc
    # end lastupdate

    def shouldRun(self, force=False):
        '''return True if the update process should run'''
        return force or self.lastupdate >= self.timeout-1
    # end shouldRun
# end ScheduledRun

def expandPath(path):
    '''expands all paths of a path'''
    return expanduser(expandvars(path))
# end expandPath

def expandPaths(envvar, error=True, validate=False):
    '''
    Takes the given environment variable, expands it, and returns
    a list of paths which have a length.

    :param boolean error:
        if True and envvar does not exist in os.environ then raise a
        KeyError

    :param boolean validate:
        if True require the path to be real before allowing it to be returned

    :except KeyError:
        raised if error is True and envvar is not in os.environ
    '''
    if error and envvar not in os.environ:
        raise KeyError("$%s is not in the environment")

    def filter_path(path):
        # do nothing if the path is blank or validation
        # is turned on and the path is not real
        if not path or validate and not exists(path):
            return False

        # in all other cases, let the path through
        return True
    # end filter_path

    return imap(
        expandPath,
        ifilter(filter_path, os.environ.get(envvar, '').split(os.pathsep))
    )
# end expandPaths

def which(program):
    '''
    returns the path to the requested program

    .. note::
        This function will not resolve aliases

    :raise OSError:
        raised if the path to the program could not be found
    '''
    # Returns the full path to the requested program.  If the path
    # is in fact valid then we have nothing left to do.
    fullpath = os.path.abspath(program)
    if os.path.isfile(fullpath):
        return fullpath

    for path in expandPaths('PATH', validate=True):
        fullpath = os.path.join(path, program)
        if os.path.isfile(fullpath):
            return fullpath

    # if all else fails, fail
    args = (program, os.environ.get('PATH'))
    raise OSError("failed to find program '%s' in %s" % args)
# end which
