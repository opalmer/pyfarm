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

import UserDict


class ReadOnlyDict(UserDict.IterableUserDict):
    '''custom dictionary that is read only'''
    err_type = NotImplementedError

    def __init__(self, dict=None, **kwargs):
        self.err_msg = "%s does not implement " % self.__class__.__name__
        self.__setup = False

        UserDict.IterableUserDict.__init__(self, dict, **kwargs)
        self.__setup = True
    # end __init__

    def __err(self, method):
        return self.err_type(self.err_msg + method.func_code.co_name)
    # end __error

    def __setitem__(self, key, item): raise self.__err(self.__setitem__)
    def __delitem__(self, key): raise self.__err(self.__delitem__)
    def setdefault(self, key, failobj=None): raise self.__err(self.setdefault)
    def clear(self): raise self.__err(self.clear)
    def pop(self, key, *args): raise self.__err(self.pop)
    def popitem(self): raise self.__err(self.popitem)

    def update(self, dict=None, **kwargs):
        if self.__setup:
            raise self.__err(self.__delitem__)
        UserDict.IterableUserDict.update(self, dict, **kwargs)
    # end update

    def copy(self, readonly=True):
        if readonly:
            return self.__class__(self.data.copy())
        return dict(self.data.copy())
    # end copy
# end ReadOnlyDict
