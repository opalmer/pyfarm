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
        if self.lastrun is None:
            return self.timeout

        else:
            delta = datetime.datetime.now() - self.lastrun
            return delta.seconds
    # end lastupdate

    def shouldRun(self, force=False):
        return force or self.lastupdate >= self.timeout
    # end shouldRun
# end ScheduledRun
