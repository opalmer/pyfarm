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

from datetime import datetime
from sqlalchemy import event, Column, ForeignKey
from sqlalchemy.orm import relationship, validates
from sqlalchemy.types import Integer, DateTime

from pyfarm.db.tables import Base, TABLE_FRAME, TABLE_JOB, TABLE_HOST, \
    FRAME_STATE_START, FRAME_STATE_STOP
from pyfarm.datatypes.enums import State

class Frame(Base):
    '''defines a single frame'''
    __tablename__ = TABLE_FRAME
    repr_attrs = ("_job", "frame", "state", "attempts", "host")
    repr_attrs_skip_none = ("host", )

    # column setup
    _job = Column(Integer, ForeignKey("%s.id" % TABLE_JOB))
    _host = Column(Integer, ForeignKey("%s.id" % TABLE_HOST))
    frame = Column(Integer, nullable=False)
    state = Column(Integer, default=State.QUEUED)
    attempts = Column(Integer, default=0)

    # time tracking
    time_submitted = Column(DateTime, default=datetime.now)
    time_started = Column(DateTime)
    time_finished = Column(DateTime)

    # relationship definitions
    job = relationship('Job', uselist=False, backref="ref_frame_job")
    host = relationship('Host', uselist=False, backref="ref_frame_host")

    def __init__(self, job, frame, state=None):
        self._job = job
        self.frame = frame

        if state is not None:
            self.state = state
    # end __init__

    @property
    def elapsed(self):
        '''returns the time elapsed since the job has started'''
        started = self.time_started
        end = datetime.now() if self.time_finished is None else \
            self.time_finished

        if started is None:
            raise ValueError("Frame %s has not been started yet" % self.id)

        delta = end - started
        return delta.days * 86400 + delta.seconds
    # end elapsed

    @validates('state')
    def validate_state(self, key, value):
        if value not in State:
            raise ValueError("value provided for %s is not valid" % key)
        return value
    # end validate_state

    @validates('frame')
    def validate_frame(self, key, value):
        if not isinstance(value, int):
            raise TypeError("expected %s to be an integer" % key)

        return value
    # end validate_frame

    @validates('_job')
    def validate_job(self, key, value):
        if isinstance(value, int):
            return value

        elif hasattr(value, '__class__') and value.__class__.__name__ == "Job":
            if value.id is not None:
                return value.id

            raise TypeError("id on Job object is None, has commit been run?")

        raise ValueError("failed to extract the value for %s" % key)
    # end validate_job
# end Frame


def frame_state_changed(target, new_value, old_value, initiator):
    '''when frame state changes update the start/end times'''
    # nothing to do here if we're not working with
    # some kind of state
    if new_value not in State:
        return

    # TODO: check state against FRAME_STATE_START/END
    # TODO: also set attempts
# end frame_state_changed

def frame_host_changed(target, new_value, old_value, initiator):
    target.state = State.ASSIGN
# end frame_host_changed

event.listen(Frame.state, 'set', frame_state_changed)
event.listen(Frame._host, 'set', frame_host_changed)
