# No shebang line, this module is meant to be imported
#
# INITIAL: Oct 18 2011
# PURPOSE: To provide a global locking mechanism
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

import threading

LOCKS = {}
DEFAULT_NAME = 'default'

def lock(name=DEFAULT_NAME):
    '''Returns the given lock name, creates it if it does not already exist'''
    if name not in LOCKS:
        LOCKS[name] = threading.Lock()

    return LOCKS[name]
# END lock


class Context(object):
    '''
    Provides a context to acquire and release a named lock

    >>> from __future__ import with_context
    >>> lock = Context()
    >>> with lock:
    ...     None
    >>>
    '''
    def __init__(self, name=DEFAULT_NAME):
        self.lock = lock(name)
    # END __init__

    def __enter__(self):
        self.lock.acquire()
        return self.lock
    # END __enter__

    def __exit__(self, type, value, traceback):
        self.lock.release()
        return self.lock
    # END __exit
# END Context