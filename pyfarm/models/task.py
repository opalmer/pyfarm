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

from textwrap import dedent
from sqlalchemy import event
from pyfarm.flaskapp import db
from pyfarm.config.enum import WorkState
from pyfarm.models.core.functions import WorkColumns, modelfor
from pyfarm.models.core.cfg import TABLE_JOB, TABLE_TASK, TABLE_AGENT
from pyfarm.models.mixins import WorkValidationMixin, StateChangedMixin


class TaskModel(db.Model, WorkValidationMixin, StateChangedMixin):
    """
    Defines a task which a child of a :class:`Job`.  This table represents
    rows which contain the individual work unit(s) for a job.
    """
    __tablename__ = TABLE_TASK
    STATE_ENUM = WorkState()
    STATE_DEFAULT = STATE_ENUM.QUEUED

    # shared work columns
    id, state, priority, time_submitted, time_started, time_finished = \
        WorkColumns(STATE_DEFAULT, "job.priority")

    attempts = db.Column(db.Integer, default=0,
                         doc=dedent("""
                         The number attempts which have been made on this
                         task. This value is auto incremented when
                         :attr:`state` changes to a value synonyms with a
                         running state."""))
    frame = db.Column(db.Float, nullable=False,
                      doc=dedent("""
                      The frame the :class:`TaskModel` will be executing."""))

    # relationships
    _agentid = db.Column(db.Integer, db.ForeignKey("%s.id" % TABLE_AGENT),
                         doc=dedent("""
                         Foreign key which stores :attr:`JobModel.id`"""))
    _jobid = db.Column(db.Integer, db.ForeignKey("%s.id" % TABLE_JOB),
                       doc=dedent("""
                       Foreign key which stores :attr:`JobModel.id`"""))
    _parenttask = db.Column(db.Integer, db.ForeignKey("%s.id" % TABLE_TASK),
                            doc=dedent("""
                            The foreign key which stores :attr:`TaskModel.id`
                            """))
    siblings = db.relationship("TaskModel",
                               backref=db.backref("task", remote_side=[id]),
                               doc=dedent("""
                               Relationship to other tasks which have the same
                               parent"""))

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
