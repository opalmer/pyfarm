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


def framerange(start, end, by=1):
    '''wrapper around xrange() which automatically adds 1 to the end frame'''
    return xrange(start, end+1, by)
# end framerange
