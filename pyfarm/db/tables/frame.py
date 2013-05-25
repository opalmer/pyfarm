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

from sqlalchemy import event, Column, ForeignKey
from sqlalchemy.orm import relationship, validates
from sqlalchemy.types import Integer

from pyfarm.logger import Logger
from pyfarm.db.tables._bases import TaskBase
from pyfarm.db.tables import Base, TABLE_FRAME, TABLE_JOB, TABLE_HOST
from pyfarm.datatypes.enums import State

logger = Logger(__name__)

class Frame(Base, TaskBase):
    """defines a single frame"""
    __tablename__ = TABLE_FRAME
    repr_attrs = ("_job", "frame", "state", "attempts", "host")
    repr_attrs_skip_none = ("host", )

    # column setup
    _job = Column(Integer, ForeignKey("%s.id" % TABLE_JOB))
    _host = Column(Integer, ForeignKey("%s.id" % TABLE_HOST))
    frame = Column(Integer, nullable=False)

    # relationship definitions
    job = relationship('Job', uselist=False, backref="ref_frame_job")
    host = relationship('Host', uselist=False, backref="ref_frame_host")

    def __init__(self, job, frame, state=None, priority=None):
        TaskBase.__init__(self, state, priority)
        self._job = job
        self.frame = frame
    # end __init__

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


# events
from pyfarm.db.tables._events import state_changed

def frame_host_changed(target, new_value, old_value, initiator):
    """set set the frame state to ASSIGN whenever the host is changed"""
    args = (target.id, new_value)
    logger.debug("changing host on frame id %s to %s" % args)
    target.state = State.ASSIGN

    # TODO: xmlrpc callback to ensure the farme is stopped if running
    if target.state == State.RUNNING:
        pass
# end frame_host_changed

event.listen(Frame.state, 'set', state_changed)
event.listen(Frame.host, 'set', frame_host_changed)
