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

import os
import UserDict

from sqlalchemy import event, Column, ForeignKey, and_
from sqlalchemy.orm import relationship, validates
from sqlalchemy.types import Integer, Boolean, Text, PickleType, String

from pyfarm import utility
from pyfarm.datatypes.enums import State, SoftwareType, EnvMergeMode
from pyfarm.datatypes import system
from pyfarm.db.tables.dependency import J2JDependency
from pyfarm.db.tables._bases import TaskBase
from pyfarm.db.tables import Base, Frame, \
    REQUEUE_FAILED, REQUEUE_MAX, TABLE_JOB, JOB_QUERY_FRAME_LIMIT, \
    TABLE_JOB_SOFTWARE, MAX_USERNAME_LENGTH, ACTIVE_FRAME_STATES


class JobSoftware(Base):
    """
    defines software which is required by a job
    """
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


class Job(Base, TaskBase):
    """base job definition"""
    __tablename__ = TABLE_JOB
    repr_attrs = (
        "id", "state", "start_frame", "end_frame", "by_frame", "priority"
    )

    # frame information
    start_frame = Column(
        Integer, nullable=False,
        doc="The frame this job starts on"
    )
    end_frame = Column(
        Integer, nullable=False,
        doc="The frame this job ends on"
    )
    by_frame = Column(
        Integer, default=1,
        doc="The offset between each frame"
    )
    batch_frame = Column(Integer, default=1)
    requeue_failed = Column(Boolean, default=REQUEUE_FAILED)
    requeue_max = Column(Integer, default=REQUEUE_MAX)

    # job related information
    _cmd = Column(Text(convert_unicode=True), nullable=False)
    args = Column(PickleType, nullable=False)
    notes = Column(Text(convert_unicode=True), default="")
    data = Column(PickleType, default=dict)
    user = Column(String(MAX_USERNAME_LENGTH), default=utility.user)
    ram = Column(Integer, default=None)
    cpus = Column(Integer, default=None)
    submission_os = Column(Integer, default=system.OS)

    # underlying environment which is represented by the environ
    # property on this class
    _environ = Column(PickleType, default=dict)
    environ_mode = Column(Integer, default=EnvMergeMode.UPDATE)

    # relationship definitions
    software = relationship(
        'JobSoftware', uselist=True, backref='ref_job_software',
        primaryjoin='(JobSoftware._job == Job.id)'
    )
    frames = relationship(
        'Frame', uselist=True, backref="ref_frames",
        primaryjoin='(Frame._job == Job.id)'
    )
    running_frames = relationship(
        'Frame', uselist=True, backref='ref_job_running_frames',
        primaryjoin='(Job.id == Frame._job) & '
                    '(Frame.state == %s)' % State.RUNNING
    )
    failed_frames = relationship(
        'Frame', uselist=True, backref='ref_job_failed_frames',
        primaryjoin='(Job.id == Frame._job) & '
                    '(Frame.state == %s)' % State.FAILED
    )

    def __init__(self, cmd, args, start_frame, end_frame, by_frame=None,
                 batch_frame=None, state=None, priority=None, environ=None,
                 environ_mode=None, data=None, requeue_max=None,
                 requeue_failed=None
        ):
        TaskBase.__init__(self, state, priority)
        self._cmd = cmd
        self.args = args
        self.start_frame = start_frame
        self.end_frame = end_frame

        if by_frame is not None:
            self.by_frame = by_frame

        if batch_frame is not None:
            self.batch_frame = batch_frame

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

        self.__dependencies = None
    # end __init__

    def hasDependencies(self):
        """returns True if the job has dependencies"""
        return bool(self.getDependencyIDs())
    # end hasDependencies

    def getDependencyIDs(self):
        """returns the dependent job ids"""
        if self.id is None:
            raise ValueError("id has not been set")

        if self.__dependencies is None:
            self.__dependencies = J2JDependency.children(self.id, self.session)

        return self.__dependencies
    # end getDependencyIDs

    @property
    def dependencies(self):
        child_jobs = self.getDependencyIDs()

        if not child_jobs:
            return child_jobs

        query = self.session.query(Job).filter(
            Job.id.in_(child_jobs)
        ).filter(
            Job.state != State.DONE
        )
        return query.all()
    # end dependencies

    @property
    def cmd(self):
        """
        returns the full path to the command

        :exception OSError:
            raised if we fail to find requested command in $PATH
        """
        return utility.which(self._cmd)
    # end cmd

    @property
    def environ(self):
        """
        returns the environment for the job while taking into
        account the different env. merge strategies
        """
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

    def queued_frames(self):
        """returns a list of frames which are currently queued to run"""
        # initial query which will retrieves frames which
        # are children of this job
        query = self.session.query(Frame).filter(Frame._job == self.id)

        # depending on the preferences we may or may
        # not want failed frames to be returned
        if REQUEUE_FAILED and REQUEUE_MAX:
            query = query.filter(and_(
                Frame.state.in_(ACTIVE_FRAME_STATES),
                Frame.attempts < REQUEUE_MAX
            ))
        else:
            query = query.filter(Frame.state == State.QUEUED)

        # reorder by priority
        query = query.order_by(Frame.priority)

        # limit the number of results of necessary
        if isinstance(JOB_QUERY_FRAME_LIMIT, int):
            query = query.limit(JOB_QUERY_FRAME_LIMIT)

        elif JOB_QUERY_FRAME_LIMIT is not False:
            raise TypeError(
                "expected an integer or False for jobsystem.job-query-frame-limit"
            )

        return query.all()
    # end queued_frames

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

    @validates('_cmd')
    def validate_command(self, key, data):
        isabs = os.path.isabs(data)
        isfile = os.path.isfile(data)

        if isabs and not isfile:
            msg = "absolute path provided but %s is not actually a file" % data
            raise OSError(msg)

        elif isabs:
            # TODO: print warning
            # TODO: add os dependency on current os type/group (linux/osx [case{insensitive}])
            pass

        # TODO: add preference for this behavior
        try:
            path = utility.which(data)

        except OSError:
            # TODO: remove self as an excluded os (perhaps type as well?)
            pass

        return data
    # end validate_command

    def createFrames(self, state=None, commit=True):
        """creates the frames for the job"""
        frames = []
        end = self.end_frame + 1
        by = 1 if not self.by_frame else self.by_frame
        state = State.QUEUED if state is None else state

        for i in xrange(self.start_frame, end, by):
            frame = Frame(self, i, state=state)
            frames.append(frame)

        self.session.add_all(frames)
        if commit:
            self.session.commit()
    # end createFrames

    def addDependencies(self, dependencies, commit=True):
        """
        Adds job to job dependencies to the current job.

        :type dependencies: list or tuple or set or Job
        :param dependencies:
            job or jobs to add as dependencies on this job object

        :param boolean commit:
            if True then commit the new dependencies to the databaseq

        :returns:
            a list of instances of :py:mod:`J2JDependency` objects.
        """
        # if the incoming object is not something we can iterate
        # over then convert it to a tuple so we can
        if not isinstance(dependencies, (set, list, tuple)):
            dependencies = (dependencies, )

        new_dependencies = []

        # validate each entry
        for entry in dependencies:
            if entry.id is None:
                raise TypeError("id has not been set, has commit been called?")

            if not isinstance(entry, Job):
                raise TypeError("can only add dependencies on jobs")

            # create a new dependency object
            dependency = J2JDependency(self, entry)
            new_dependencies.append(dependency)

        if commit:
            self.session.add_all(new_dependencies)
            self.session.commit()

        return new_dependencies
    # end addDependencies
# end Job

# events
from pyfarm.db.tables._events import state_changed
event.listen(Job.state, 'set', state_changed)
