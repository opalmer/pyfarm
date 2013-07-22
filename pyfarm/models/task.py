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

from datetime import datetime
from sqlalchemy import event
from pyfarm.flaskapp import db
from pyfarm.config.enum import WorkState
from pyfarm.models.core import (
    DBCFG, TABLE_JOB, TABLE_TASK, TABLE_AGENT, IDColumn, modelfor)
from pyfarm.models.mixins import StateValidationMixin, StateChangedMixin


class TaskModel(db.Model, StateValidationMixin, StateChangedMixin):
    """Defines task which a child of a :class:`.Job`"""
    __tablename__ = TABLE_TASK
    STATE_ENUM = WorkState()
    STATE_DEFAULT = STATE_ENUM.QUEUED

    id = IDColumn()

    # task state/general data
    state = db.Column(db.Integer, default=STATE_DEFAULT, nullable=False)
    priority = db.Column(db.Integer, default=DBCFG.get("task.priority"),
                         nullable=False)
    attempts = db.Column(db.Integer, default=0)
    frame = db.Column(db.Integer, nullable=False)

    # time information
    time_submitted = db.Column(db.DateTime, default=datetime.now)
    time_started = db.Column(db.DateTime)
    time_finished = db.Column(db.DateTime)

    # relationships
    _agentid = db.Column(db.Integer, db.ForeignKey("%s.id" % TABLE_AGENT))
    _jobid = db.Column(db.Integer, db.ForeignKey("%s.id" % TABLE_JOB))
    _parenttask = db.Column(db.Integer, db.ForeignKey("%s.id" % TABLE_TASK))
    siblings = db.relationship("TaskModel", backref=db.backref("task",
                                                         remote_side=[id]))

    @staticmethod
    def agentChangedEvent(target, new_value, old_value, initiator):
        """set the state to ASSIGN whenever the agent is changed"""
        if new_value is not None:
            target.state = target.STATE_ENUM.ASSIGN

event.listen(TaskModel._agentid, "set", TaskModel.agentChangedEvent)
event.listen(TaskModel.state, "set", TaskModel.stateChangedEvent)


class Task(TaskModel):
    """
    Provides :meth:`__init__` for :class:`TaskModel` so the model can
    be instanced with initial values.
    """
    def __init__(self, job, frame, parent_task=None, state=None,
                 priority=None, attempts=None, agent=None):
        # build parent job id
        if modelfor(job, TABLE_JOB):
            jobid = job.jobid
            if jobid is None:
                raise ValueError("`job` with null id provided")
        elif isinstance(job, int):
            jobid = job
        else:
            raise ValueError("failed to determine job id")

        # build parent task id
        if parent_task is None:
            parent_taskid = None
        elif modelfor(parent_task, TABLE_TASK):
            parent_taskid = parent_task.id
            if parent_taskid is None:
                raise ValueError("`parent_task` with null id provided")
        elif isinstance(parent_task, int):
            parent_taskid = parent_task
        else:
            raise ValueError("failed to determine parent task id")

        # build agent id
        if agent is None:
            agentid = None
        elif modelfor(agent, TABLE_AGENT):
            agentid = agent.id
            if agentid is None:
                raise ValueError("`agent` with null id provided")
        elif isinstance(agent, int):
            agentid = agent
        else:
            raise ValueError("failed to determine agent id")

        self._jobid = jobid
        self.frame = frame

        if parent_taskid is not None:
            self._parenttask = parent_taskid

        if agentid is not None:
            self._agentid = agentid

        if state is not None:
            self.state = state

        if priority is not None:
            self.priority = priority

        if attempts is not None:
            self.attempts = attempts
