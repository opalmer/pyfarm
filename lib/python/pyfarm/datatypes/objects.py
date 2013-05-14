# No shebang line, this module is meant to be imported
#
# Copyright 2013 Oliver Palmer
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#    http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

import UserDict
import datetime

class ReadOnlyDict(UserDict.IterableUserDict):
    """custom dictionary that is read only"""
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


class ScheduledRun:
    # old style class since twisted classes are also old style
    """
    Basic class which informs child classes if they should
    perform their indicated function
    """
    def __init__(self, timeout):
        self.timeout = timeout
        self.lastrun = None
    # end __init__

    @property
    def lastupdate(self):
        """
        returns the time since last update or the timeout itself
        if lastrun has not been set
        """
        if self.lastrun is None:
            return self.timeout

        else:
            delta = datetime.datetime.now() - self.lastrun
            return delta.seconds + 1 # accounts for most inaccuracies in time calc
    # end lastupdate

    def shouldRun(self, force=False):
        """return True if the update process should run"""
        return force or self.lastupdate >= self.timeout-1
    # end shouldRun
# end ScheduledRun
