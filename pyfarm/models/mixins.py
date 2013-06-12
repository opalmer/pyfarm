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

from warnings import warn

from datetime import datetime
from sqlalchemy.orm import validates
from pyfarm.utility import randint
from pyfarm.flaskapp import db


class StateValidationMixin(object):
    """
    Mixin that adds a `state` column and uses a class
    level `STATE_ENUM` attribute to assist in validation.
    """
    @validates("state")
    def validate_state(self, key, value):
        if value not in self.STATE_ENUM.values():
            # string which represents what states are valid
            valid_states = ", ".join(
                "%s (%s)" % (value, self.STATE_ENUM.get(value))
                for value in sorted(self.STATE_ENUM.values()))

            msg = "%s is not a valid state, valid states " % value
            msg += "are %s" % valid_states
            raise ValueError(msg)

        return value


class RandIdMixin(object):
    """
    Mixin that provides an `id` column which generates a
    value using :func:`.randint`
    """
    id = db.Column(
        db.Integer, primary_key=True, default=randint,
        nullable=False, unique=True)


class StateChangedMixin(object):
    """
    Mixin which adds a static method to be used when the model
    state changes
    """
    @staticmethod
    def stateChangedEvent(target, new_value, old_value, initiator):
        """update the datetime objects depending on the new value"""
        if target.id is None:
            pass

        elif new_value == target.STATE_ENUM.RUNNING:
            target.time_started = datetime.now()
            target.attempts += 1

        elif new_value in (target.STATE_ENUM.DONE, target.STATE_ENUM.FAILED):
            if target.time_started is None:
                msg = "job %s has not been started yet, state is " % target.id
                msg += "being set to %s" % target.STATE_ENUM.get(new_value)
                warn(msg)

            target.time_finished = datetime.now()