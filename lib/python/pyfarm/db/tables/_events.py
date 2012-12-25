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

'''
contains functions for events which are can be used by
multiple tables
'''

from datetime import datetime

from pyfarm.logger import Logger
from pyfarm.datatypes.enums import State

logger = Logger(__name__)

def state_changed(target, new_value, old_value, initiator):
    '''when job state changes update the start/end times'''
    if new_value == State.RUNNING:
        target.time_started = datetime.now()
        target.attempts += 1

    elif new_value in (State.DONE, State.FAILED):
        # job should have been started at some point
        if target.time_started is None and target.id is not None:
            msg = "job %s has not been started yet, state is being " % target.id
            msg += "set to %s" % State.get(new_value)
            logger.warning(msg)

        target.time_finished = datetime.now()

    if new_value != State.QUEUED:
        args = (target.__class__.__name__, target.id, State.get(new_value))
        logger.debug("%s(id=%s) state changed to %s" % args)
# end state_changed
