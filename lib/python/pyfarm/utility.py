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
import sqlalchemy.util

class NamedTupleRow(sqlalchemy.util.NamedTuple):
    def __repr__(self):
        values = []
        for key, value in self.__dict__.iteritems():
            if key != "_labels":
                values.append("%s=%s" % (key, repr(value)))

        return "Row(%s)" % ", ".join(values)
    # end __repr__
# end NamedTupleRow


# old style class since twisted classes are also old style
class ScheduledRun:
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


def which(program):
    '''
    returns the path to the requested program

    :raise OSError:
        raised if the path to the program could not be found
    '''
    # Returns the full path to the requested program.  If the path
    # is in fact valid then we have nothing left to do.
    fullpath = os.path.abspath(program)
    if os.path.isfile(fullpath):
        return fullpath

    # though rare account for problems with $PATH not
    # being in the environment
    if 'PATH' not in os.environ:
        raise EnvironmentError("$PATH is not defined in the environment")

    envpath = os.environ.get('PATH')
    for path in ( path for path in envpath.split(os.pathsep) if path ):
        fullpath = os.path.join(path, program)
        if os.path.isfile(fullpath):
            return fullpath

    # if all else fails, fail
    raise OSError("failed to find program '%s' in %s" % (program, envpath))
# end which
