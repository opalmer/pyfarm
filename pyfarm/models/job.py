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
from datetime import datetime
from UserDict import UserDict

try:
    import json
except ImportError:
    import simplejson as json

from sqlalchemy.orm import validates

from pyfarm.flaskapp import db
from pyfarm.config.enum import WorkState
from pyfarm.models.constants import (
    DBDATA, TABLE_JOB, TABLE_JOB_TAGS, TABLE_JOB_SOFTWARE
)
from pyfarm.models.mixins import RandIdMixin, StateValidationMixin


class JobTags(db.Model):
    __tablename__ = TABLE_JOB_TAGS
    _jobid = db.Column(db.Integer, db.ForeignKey("%s.id" % TABLE_JOB),
                         primary_key=True)
    tag = db.Column(db.String)


class JobSoftware(db.Model):
    __tablename__ = TABLE_JOB_SOFTWARE
    _jobid = db.Column(db.Integer, db.ForeignKey("%s.id" % TABLE_JOB),
                         primary_key=True)
    software = db.Column(db.String)


class Job(db.Model, RandIdMixin, StateValidationMixin):
    """Defines task which a child of a :class:`.Job`"""
    __tablename__ = TABLE_JOB
    STATE_ENUM = WorkState()
    STATE_DEFAULT = STATE_ENUM.QUEUED

    state = db.Column(db.Integer, default=STATE_DEFAULT, nullable=False)
    priority = db.Column(db.Integer, default=DBDATA.get("job.priority"),
                         nullable=False)
    time_submitted = db.Column(db.DateTime, default=datetime.now)
    time_started = db.Column(db.DateTime)
    time_finished = db.Column(db.DateTime)

    # TODO: need to test environ/_environ setup
    _environ = db.Column(db.UnicodeText)

    # relationships
    # TODO: add relationship to agents
    tasks = db.relationship("Task", backref="job", lazy="dynamic")
    tags = db.relationship("JobTags", backref="job", lazy="dynamic")
    software = db.relationship("JobSoftware", backref="job", lazy="dynamic")

    @property
    def environ(self):
        if self._environ is None:
            return os.environ.copy()

        value = json.loads(self._environ)
        assert isinstance(value, dict), "expected a dictionary from _environ"
        return value

    @environ.setter
    def environ(self, value):
        if isinstance(value, dict):
            value = json.dumps(value)
        elif isinstance(value, UserDict):
            value = json.dumps(value.data)

        self._environ = value

    @validates("environ")
    def validation_environ(self, key, value):
        if not isinstance(value, (dict, UserDict, basestring)):
            raise TypeError("expected a dictionary or string for %s" % key)

        return value