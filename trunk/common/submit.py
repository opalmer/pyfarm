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
Used to verify and submit jobs
'''

import inspect

from common import logger
from common.preferences import prefs

from twisted.python import log

def validJobtype(jobtype):
    '''ensure the jobtype exists and return its module or raise an error'''
    mappings = prefs.get('jobtypes.mappings')
    if jobtype not in mappings:
        msg = "jobtype %s does not have a mapping " % jobtype
        msg += "in jobtypes.mappings, attempting to import directly"
        log.msg(msg)

        try:
            module = __import__('jobtypes.%s' % jobtype, fromlist=['jobtypes'])

        except ImportError:
            raise ImportError("no such jobtype '%s'" % jobtype)

    else:
        modulename = mappings.get(jobtype)
        module = __import__('jobtypes.%s' % modulename, fromlist=['jobtypes'])

    log.msg("found module for %s jobtype: %s" % (jobtype, module.__file__))

    # check to make sure the Job class exists
    if not hasattr(module, 'Job') or not inspect.isclass(module.Job):
        raise AttributeError('%s jobtype missing the Job class' % jobtype)

    return module
# end validJobtype

def job(jobtype):
    '''
    Used to submit a new job with a range of frames.

    :param string jobtype:
        the name of the jobtype to pull from jobtypes.mappings
    '''
    module = validJobtype(jobtype)
    print module.Job
# end job


if __name__ == '__main__':
    job('maya')
