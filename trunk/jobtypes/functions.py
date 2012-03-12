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
functions for jobtype system related queries and setup
'''

import os
import inspect
import logging

from twisted.python import log

from common.preferences import prefs

SKIP_MODULES = prefs.get('jobtypes.excluded-names')
CWD = os.path.dirname(os.path.abspath(__file__))

def jobtypes():
    '''returns a list of all valid jobtypes as strings'''
    types = set()

    for listing in os.listdir(CWD):
        filename, extension = os.path.splitext(listing)
        if filename not in SKIP_MODULES:
            # load the module and ensure it has an attribute 'Job'
            # that is a class
            module = __import__(filename, locals(), globals())
            if hasattr(module, 'Job') and inspect.isclass(module.Job):
                types.add(filename)

            else:
                log.msg(
                    "'Job' is not a class in %s" % module.__file__,
                    level=logging.WARNING,
                    system="jobtypes.functions.jobtypes"
                )

    return list(types)
# end jobtypes

def load(name):
    '''
    loads and returns a specific jobtype so long as it exists
    and contains the Job class

    :exception NameError:
        raised if the a valid jobtype with the given
        name does not exist

    :return:
        return the jobtype's class object
    '''
    if name not in jobtypes():
        raise NameError("no such jobtype %s" % name)

    module = __import__(name, locals(), globals(), fromlist=['jobtypes'])
    return module.Job
# end load

if __name__ == '__main__':
    print load('mayatomr')
