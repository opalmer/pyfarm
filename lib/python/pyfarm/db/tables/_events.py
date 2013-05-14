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

"""
contains functions for events which are can be used by
multiple tables
"""

from datetime import datetime

from pyfarm.logger import Logger
from pyfarm.datatypes.enums import State

logger = Logger(__name__)

def state_changed(target, new_value, old_value, initiator):
    """when job state changes update the start/end times"""
    # object has not been committed yet
    if target.id is None:
        return

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
