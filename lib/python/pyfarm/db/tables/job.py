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

from sqlalchemy import Column, ForeignKey, and_
from sqlalchemy.orm import relationship
from sqlalchemy.types import Integer, Boolean

from pyfarm.db.tables import Base, Frame
from pyfarm.preferences import prefs
from pyfarm.datatypes.enums import State, ACTIVE_FRAME_STATES, \
    ACTIVE_DEPENDENCY_STATES

REQUEUE_MAX = prefs.get('jobtypes.defaults.requeue-max')
REQUEUE_FAILED = prefs.get('jobtypes.defaults.requeue-failed')

class Dependency(Base):
    '''
    Defines a dependency
    '''
    __tablename__ = "pyfarm_job_dependency"
    repr_attrs = ("parent", "dependency")

    parent = Column(Integer, ForeignKey("pyfarm_jobs.id"), nullable=False)
    dependency = Column(Integer, ForeignKey("pyfarm_jobs.id"), nullable=False)

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
    __tablename__ = "pyfarm_jobs"
    repr_attrs = ("id", "state")

    state = Column(Integer, default=State.QUEUED)
    requeue_failed = Column(Boolean, default=REQUEUE_FAILED)
    requeue_max = Column(Integer, default=REQUEUE_MAX)

    # relationship definitions
    frames = relationship('Frame', uselist=True, backref="ref_frames")
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

    def __init__(self, state=None):
        if state is not None:
            self.state = state
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

        # iterate over all the dependencies we found and create a list
        # of running dependencies
        query = self.session.query(Job).filter(and_(
            Job.id.in_(set((dep.dependency for dep in all_dependencies))),
            Job.state.in_(ACTIVE_DEPENDENCY_STATES)
        ))

        return query.all()
    # end dependencies
# end Job
