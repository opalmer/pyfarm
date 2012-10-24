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

import os
import getpass
from datetime import datetime

from sqlalchemy import Column, ForeignKey, and_
from sqlalchemy.orm import relationship
from sqlalchemy.types import Integer, Boolean, DateTime, Text, \
    PickleType, String

from pyfarm.datatypes.enums import State, ACTIVE_FRAME_STATES, \
    ACTIVE_DEPENDENCY_STATES
from pyfarm.db.tables import Base, Frame, \
    REQUEUE_FAILED, REQUEUE_MAX, TABLE_JOB, TABLE_JOB_DEPENDENCY, \
    DEFAULT_PRIORITY

class Dependency(Base):
    '''
    Defines a dependency between a parent job and a child
    '''
    __tablename__ = TABLE_JOB_DEPENDENCY
    repr_attrs = ("parent", "dependency")

    parent = Column(Integer, ForeignKey("%s.id" % TABLE_JOB), nullable=False)
    dependency = Column(Integer, ForeignKey("%s.id" % TABLE_JOB), nullable=False)

    def __init__(self, parent, dependency):
        if isinstance(parent, Job):
            parent = parent.id

        if isinstance(dependency, Job):
            dependency = dependency.id

        self.parent = parent
        self.dependency = dependency
    # end __init__
# end Dependency


class Job(Base):
    '''base job definition'''
    __tablename__ = TABLE_JOB
    repr_attrs = (
        "id", "state", "start_frame", "end_frame", "by_frame", "priority"
    )

    # frame information
    start_frame = Column(Integer, nullable=False)
    end_frame = Column(Integer, nullable=False)
    by_frame = Column(Integer, default=1)
    batch_frame = Column(Integer, default=1)

    # state, requeue, and priority
    state = Column(Integer, default=State.QUEUED)
    priority = Column(Integer, default=DEFAULT_PRIORITY)
    requeue_failed = Column(Boolean, default=REQUEUE_FAILED)
    requeue_max = Column(Integer, default=REQUEUE_MAX)
    time_submitted = Column(DateTime, default=datetime.now)

    # job related information
    cmd = Column(Text(convert_unicode=True), nullable=False)
    args = Column(PickleType, nullable=False)
    notes = Column(Text(convert_unicode=True), default="N/A")
    environ = Column(PickleType, default=os.environ.copy)
    data = Column(PickleType, default=dict)
    user = Column(String(256), default=getpass.getuser)
    ram = Column(Integer, default=None)
    cpus = Column(Integer, default=None)

    # relationship definitions
    frames = relationship(
        'Frame', uselist=True, backref="ref_frames",
        primaryjoin='(Frame.jobid == Job.id)'
    )
    running_frames = relationship(
        'Frame', uselist=True, backref="ref_job_running_frames",
        primaryjoin='(Job.id == Frame.jobid) & '
                    '(Frame.state == %s)' % State.RUNNING
    )
    failed_frames = relationship(
        'Frame', uselist=True, backref="ref_job_failed_frames",
        primaryjoin='(Job.id == Frame.jobid) & '
                    '(Frame.state == %s)' % State.FAILED
    )

    # TODO: attributes for new columns above
    def __init__(self, cmd, args, start_frame, end_frame, by_frame=None,
                 batch_frame=None, state=None, priority=None, requeue_max=None,
                 requeue_failed=None):
        self.cmd = cmd
        self.args = args
        self.start_frame = start_frame
        self.end_frame = end_frame

        if by_frame is not None:
            self.by_frame = by_frame

        if self.batch_frame is not None:
            self.batch_frame = batch_frame

        if priority is not None:
            self.priority = priority

        if state is not None:
            self.state = state

        if requeue_failed is None:
            _requeue_failed = requeue_failed

        else:
            self.requeue_failed = requeue_failed
            _requeue_failed = self.requeue_failed

        if requeue_max and not _requeue_failed:
            raise ValueError(
                "requeue_max set but requeue_failed evals as None"
            )

        elif requeue_max is not None:
            self.requeue_max = requeue_max


    # end __init__

    @property
    def queued_frames(self):
        '''
        returns a list of frames which are currently queued to run

        .. note::
            depending on the preferences this
        '''
        query = self.session.query(Frame).filter(Frame.jobid == self.id)

        if REQUEUE_FAILED and REQUEUE_MAX:
            query = query.filter(and_(
                Frame.state.in_(ACTIVE_FRAME_STATES),
                Frame.attempts < REQUEUE_MAX
            ))
        else:
            query = query.filter(Frame.state == State.QUEUED)

        return query.all()
    # end queued_frames

    @property
    def dependencies(self):
        '''returns a list of jobs which we are waiting on to complete'''
        all_dependencies = self.session.query(Dependency).filter(
            Dependency.parent == self.id
        )
        if not all_dependencies.count():
            return []

        # TODO: we should probably have the option of checking parent dependencies too
        # iterate over all the dependencies we found and create a list
        # of running dependencies
        query = self.session.query(Job).filter(and_(
            Job.id.in_(set((dep.dependency for dep in all_dependencies))),
            Job.state.in_(ACTIVE_DEPENDENCY_STATES)
        ))

        return query.all()
    # end dependencies
# end Job
