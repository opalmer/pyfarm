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
from sqlalchemy import event

from pyfarm.flaskapp import db
from pyfarm.config.enum import WorkState
from pyfarm.models.constants import DBDATA, TABLE_JOB, TABLE_TASK, TABLE_AGENT
from pyfarm.models.mixins import (
    RandIdMixin, StateValidationMixin, StateChangedMixin)


class Task(db.Model, RandIdMixin, StateValidationMixin, StateChangedMixin):
    """Defines task which a child of a :class:`.Job`"""
    __tablename__ = TABLE_TASK
    STATE_ENUM = WorkState()
    STATE_DEFAULT = STATE_ENUM.QUEUED

    # relational ids
    _agentid = db.Column(db.Integer, db.ForeignKey("%s.id" % TABLE_AGENT))
    _jobid = db.Column(db.Integer, db.ForeignKey("%s.id" % TABLE_JOB))
    state = db.Column(db.Integer, default=STATE_DEFAULT, nullable=False)
    priority = db.Column(db.Integer, default=DBDATA.get("task.priority"),
                         nullable=False)
    attempts = db.Column(db.Integer, default=0)
    frame = db.Column(db.Integer)
    time_submitted = db.Column(db.DateTime, default=datetime.now)
    time_started = db.Column(db.DateTime)
    time_finished = db.Column(db.DateTime)

    @staticmethod
    def agentChangedEvent(target, new_value, old_value, initiator):
        """set the state to ASSIGN whenever the agent is changed"""
        if new_value is not None:
            target.state == target.STATE_ENUM.ASSIGN


event.listen(Task._agentid, "set", Task.agentChangedEvent)
event.listen(Task.state, "set", Task.stateChangedEvent)