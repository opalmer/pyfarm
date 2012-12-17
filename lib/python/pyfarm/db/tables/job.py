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
import UserDict
from datetime import datetime

from sqlalchemy import Column, ForeignKey, and_
from sqlalchemy.orm import relationship, validates
from sqlalchemy.types import Integer, Boolean, DateTime, Text, \
    PickleType, String

from pyfarm.datatypes.enums import State, ACTIVE_FRAME_STATES, \
    ACTIVE_DEPENDENCY_STATES, SoftwareType, EnvMergeMode

from pyfarm.db.tables import Base, Frame, \
    REQUEUE_FAILED, REQUEUE_MAX, TABLE_JOB, TABLE_JOB_DEPENDENCY, \
    DEFAULT_PRIORITY, TABLE_JOB_SOFTWARE, MAX_USERNAME_LENGTH

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


class JobSoftware(Base):
    '''
    defines software which is required by a job
    '''
    __tablename__ = TABLE_JOB_SOFTWARE
    repr_attrs = ("_job", "type")

    _job = Column(Integer, ForeignKey("%s.id" % TABLE_JOB), nullable=False)
    job = relationship('Job', uselist=False, backref="ref_software_job")
    type = Column(Integer, nullable=False)

    def __init__(self, job, type):
        self.type = type
        self.jobid = job.id if isinstance(job, Job) else job
    # end __init__

    @validates('type')
    def validate_type(self, key, data):
        if isinstance(data, (str, unicode)):
            data = SoftwareType[data]

        if data not in SoftwareType:
            raise ValueError("invalid value for SoftwareType")

        return data
    # end validate_type
# end JobSoftware

# TODO: test software relationship
# TODO: verify all required attributes are present
# TODO: verify properties are present for correct columns (_environ example)
# TODO: verify proper column validation

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

    # time tracking
    time_submitted = Column(DateTime, default=datetime.now)
    time_started = Column(DateTime)
    time_finished = Column(DateTime)

    # job related information
    cmd = Column(Text(convert_unicode=True), nullable=False)
    args = Column(PickleType, nullable=False)
    notes = Column(Text(convert_unicode=True), default="N/A")
    data = Column(PickleType, default=dict)
    user = Column(String(MAX_USERNAME_LENGTH), default=getpass.getuser)
    ram = Column(Integer, default=None)
    cpus = Column(Integer, default=None)

    # underlying environment which is represented by the environ
    # property on this class
    _environ = Column(PickleType, default=dict)
    environ_mode = Column(Integer, default=EnvMergeMode.UPDATE)

    # relationship definitions
    software = relationship(
        'JobSoftware', uselist=True, backref="ref_job_software",
        primaryjoin='(JobSoftware._job == Job.id)'
    )
    frames = relationship(
        'Frame', uselist=True, backref="ref_frames",
        primaryjoin='(Frame._job == Job.id)'
    )
    running_frames = relationship(
        'Frame', uselist=True, backref="ref_job_running_frames",
        primaryjoin='(Job.id == Frame._job) & '
                    '(Frame.state == %s)' % State.RUNNING
    )
    failed_frames = relationship(
        'Frame', uselist=True, backref="ref_job_failed_frames",
        primaryjoin='(Job.id == Frame._job) & '
                    '(Frame.state == %s)' % State.FAILED
    )

    def __init__(self, cmd, args, start_frame, end_frame, by_frame=None,
                 batch_frame=None, state=None, priority=None, environ=None,
                 environ_mode=None, data=None, requeue_max=None,
                 requeue_failed=None
        ):
        self.cmd = cmd
        self.args = args
        self.start_frame = start_frame
        self.end_frame = end_frame

        if by_frame is not None:
            self.by_frame = by_frame

        if batch_frame is not None:
            self.batch_frame = batch_frame

        if state is not None:
            self.state = state

        if priority is not None:
            self.priority = priority

        if environ is not None:
            self._environ = environ

        if environ_mode is not None:
            self.environ_mode = environ_mode

        if data is not None:
            self.data = data

        if requeue_max is not None:
            self.requeue_max = requeue_max

        if requeue_failed is not None:
            self.requeue_failed = requeue_failed

    # end __init__

    @property
    def environ(self):
        '''
        returns the environment for the job while taking into
        account the different env. merge strategies
        '''
        mode = self.environ_mode

        # simply update the current environment with the
        # incoming environment
        if mode == EnvMergeMode.UPDATE:
            environ = os.environ.copy()
            environ.update(self._environ)
            return environ

        # take a copy of the current environment and then
        # create keys that do not exist using the provided
        # environment
        elif mode == EnvMergeMode.FILL:
            environ = os.environ.copy()
            for key, value in self._environ.iteritems():
                environ.setdefault(key, value)

            return environ

        # do nothing, replace the current environment with
        # the provided environment
        elif mode == EnvMergeMode.REPLACE:
            return self._environ
    # end environ

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

    @property
    def elapsed(self):
        '''returns the time elapsed since the job has started'''
        started = self.time_started
        finished = self.time_finished

        if started is None:
            raise ValueError("Job %s has not been started yet" % self.id)

        if finished is None:
            end = datetime.now()

        delta = end - started
        return delta.days * 86400 + delta.seconds
    # end elapsed

    @validates('environ_mode')
    def validate_environ_mode(self, key, value):
        if value not in EnvMergeMode.values():
            raise ValueError("%s is not a valid value for %s" % (key, value))
        return value
    # end validate_environ_mode

    @validates('_environ', 'data')
    def validate_dict(self, key, environ):
        if not isinstance(environ, (UserDict.UserDict, dict)):
            raise TypeError("%s must be a dictionary" % key)

        return environ
    # end validate_dict

    @validates('end_frame')
    def validate_frange(self, key, value):
        if value < self.start_frame:
            raise ValueError("%s cannot be less than start_frame" % key)
        return value
    # end validate_frange

    @validates('state')
    def validate_state(self, key, state):
        if state not in State:
            state_names = [ State.get(state) for state in State ]
            raise ValueError("%s must be in %s" % (key, state_names))

        return state
    # end validate_state

    @validates('args')
    def validate_list(self, key, args):
        if not isinstance(args, (list, tuple)):
            raise TypeError("%s must be a list or tuple" % key)

        return args
    # end validate_list

    @validates('cpus', 'ram', 'batch_frame', 'by_frame')
    def validate_positive_int(self, key, data):
        if data < 1:
            raise ValueError("%s value must be greater than zero" % key)

        return data
    # end validate_positive_int

    def createFrames(self, state=None, commit=True):
        '''creates the frames for the job'''
        frames = []
        end = self.end_frame + 1
        by = 1 if not self.by_frame else self.by_frame

        for i in xrange(self.start_frame, end, by):
            frame = Frame(self, i, state=state)
            frames.append(frame)

        self.session.add_all(frames)
        if commit:
            self.session.commit()
    # end createFrames
# end Job
