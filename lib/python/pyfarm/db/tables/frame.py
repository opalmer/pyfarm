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

from sqlalchemy import Column, ForeignKey
from sqlalchemy.orm import relationship
from sqlalchemy.types import Integer

from pyfarm.db.tables import Base, TABLE_FRAME, TABLE_JOB, TABLE_HOST
from pyfarm.datatypes.enums import State

class Frame(Base):
    '''defines a single frame'''
    __tablename__ = TABLE_FRAME
    repr_attrs = ("frame", "host", "state")

    # column setup
    _job = Column(Integer, ForeignKey("%s.id" % TABLE_JOB))
    _host = Column(Integer, ForeignKey("%s.id" % TABLE_HOST))
    frame = Column(Integer, nullable=False)
    state = Column(Integer, default=State.QUEUED)

    # relationship definitions
    job = relationship('Job', uselist=False, backref="ref_frame_job")
    host = relationship('Host', uselist=False, backref="ref_frame_host")

    def __init__(self, job, frame, state=State.QUEUED, host=None):
        self.frame = frame

        # state setup
        if not isinstance(state, int):
            self.state = State.get(state)

        # job setup
        if isinstance(job, int):
            self._job = job

        elif job is not None:
            value = getattr(job, 'id')
            if isinstance(value, int):
                self._job = value

        # host setup
        if isinstance(host, int):
            self._host = host

        elif host is not None:
            value = getattr(host, 'id')
            if isinstance(value, int):
                self._host = value
    # end __init__
# end Frame
