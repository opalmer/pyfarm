# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2013 Oliver Palmer
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


'''module for modifying network information in the database'''

from pyfarm import errors
from pyfarm.db.tables import masters
from pyfarm.db.modify import base

__all__ = ['master']

def master(hostname, **columns):
    '''
    :param string hostname:
        the hostname we are attempting to modify

    :param columns:
        columns to update for the given host

    :exception pyfarm.errors.HostNotFound:
        raised if t we could not find the host in

    :exception ValueError:
        raised if there was a problem with one or more of
        the input arguments
    '''
    return base.modify(
        masters, 'hostname', hostname,
        exception_duplicate=errors.DuplicateHost,
        exception_notfound=errors.NotFoundError,
        **columns
    )
# end master
