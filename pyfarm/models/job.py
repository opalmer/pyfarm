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
from warnings import warn
from datetime import datetime
from UserDict import UserDict

try:
    import pwd
except ImportError:
    pwd = None

try:
    import json
except ImportError:
    import simplejson as json

try:
    property.setter
except AttributeError:
    from pyfarm.backports import _property as property

from sqlalchemy import event
from sqlalchemy.orm import validates

from pyfarm.flaskapp import db
from pyfarm.config.enum import WorkState
from pyfarm.warning import ConfigurationWarning
from pyfarm.models.core import (
    DBCFG, TABLE_JOB, TABLE_JOB_TAGS, TABLE_JOB_SOFTWARE, IDColumn
)
from pyfarm.models.mixins import StateValidationMixin, StateChangedMixin


class JobTagsModel(db.Model):
    __tablename__ = TABLE_JOB_TAGS
    _jobid = db.Column(db.Integer, db.ForeignKey("%s.id" % TABLE_JOB),
                         primary_key=True)
    id = IDColumn()
    tag = db.Column(db.String)


class JobSoftwareModel(db.Model):
    __tablename__ = TABLE_JOB_SOFTWARE
    _jobid = db.Column(db.Integer, db.ForeignKey("%s.id" % TABLE_JOB),
                         primary_key=True)
    id = IDColumn()
    software = db.Column(db.String)


class JobModel(db.Model, StateValidationMixin, StateChangedMixin):
    """Defines task which a child of a :class:`.JobModel`"""
    __tablename__ = TABLE_JOB
    STATE_ENUM = WorkState()
    STATE_DEFAULT = STATE_ENUM.QUEUED

    id = IDColumn()

    # job state/general data not specific to a task
    state = db.Column(db.Integer, default=STATE_DEFAULT)
    priority = db.Column(db.Integer, default=DBCFG.get("job.priority"))
    user = db.Column(db.String(DBCFG.get("job.max_username_length")))
    notes = db.Column(db.Text, default="")

    # time information
    time_submitted = db.Column(db.DateTime, default=datetime.now)
    time_started = db.Column(db.DateTime)
    time_finished = db.Column(db.DateTime)

    # task data
    cmd = db.Column(db.String)
    start = db.Column(db.Float)
    end = db.Column(db.Float)
    by = db.Column(db.Float, default=1)
    batch = db.Column(db.Integer, default=DBCFG.get("job.batch"))
    requeue = db.Column(db.Integer, default=DBCFG.get("job.requeue"))
    cpus = db.Column(db.Integer, default=DBCFG.get("job.cpus"))
    ram = db.Column(db.Integer, default=DBCFG.get("job.ram"))

    # underlying storage for properties
    _environ = db.Column(db.Text)
    _args = db.Column(db.Text)
    _data = db.Column(db.Text)

    # relationships
    _parentjob = db.Column(db.Integer, db.ForeignKey("%s.id" % TABLE_JOB))
    siblings = db.relationship("JobModel", backref=db.backref("parent",
                                                         remote_side=[id]))
    tasks = db.relationship("TaskModel", backref="job", lazy="dynamic")
    tasks_done = db.relationship("TaskModel", lazy="dynamic",
        primaryjoin="(TaskModel.state == %s) & "
                    "(TaskModel._jobid == JobModel.id)" % STATE_ENUM.DONE)
    tasks_failed = db.relationship("TaskModel", lazy="dynamic",
        primaryjoin="(TaskModel.state == %s) & "
                    "(TaskModel._jobid == JobModel.id)" % STATE_ENUM.FAILED)
    tasks_queued = db.relationship("TaskModel", lazy="dynamic",
        primaryjoin="(TaskModel.state == %s) & "
                    "(TaskModel._jobid == JobModel.id)" % STATE_ENUM.QUEUED)
    tags = db.relationship("JobTagsModel", backref="job", lazy="dynamic")
    software = db.relationship("JobSoftwareModel", backref="job", lazy="dynamic")

    @property
    def environ(self):
        if not self._environ:
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
        else:
            raise TypeError("expected a dict or UserDict object for `environ`")

        self._environ = value

    @property
    def data(self):
        return json.loads(self._data)

    @data.setter
    def data(self, value):
        self._data = json.dumps(value)

    @property
    def args(self):
        return json.loads(self._args)

    @args.setter
    def args(self, value):
        assert isinstance(value, list), "expected a list for `args`"
        self._args = json.dumps(value)

    @validates("environ")
    def validation_environ(self, key, value):
        if not isinstance(value, (dict, UserDict, basestring)):
            raise TypeError("expected a dictionary or string for %s" % key)

        return value

    @validates("user")
    def validate_user(self, key, value):
        max_length = DBCFG.get("job.max_username_length")
        if len(value) > max_length:
            msg = "max user name length is %s" % max_length
            raise ValueError(msg)

        return value

    @validates("_environ")
    def validate_json(self, key, value):
        try:
            json.dumps(value)
        except Exception, e:
            raise ValueError("failed to dump `%s` to json: %s" % (key, e))

        return value

    @validates("user")
    def validate_user(self, key, value):
        if pwd is not None:
            try:
                pwd.getpwnam(value)
            except:
                warn("no such user `%s` could be found" % value,
                     ConfigurationWarning)

        return value

    @validates("ram", "cpus")
    def validate_resource(self, key, value):
        if value is None:
            return value

        min_value = DBCFG.get("agent.min_%s" % key)
        max_value = DBCFG.get("agent.max_%s" % key)

        # quick sanity check of the incoming config
        assert isinstance(min_value, int), "db.min_%s must be an integer" % key
        assert isinstance(max_value, int), "db.max_%s must be an integer" % key
        assert min_value >= 1, "db.min_%s must be > 0" % key
        assert max_value >= 1, "db.max_%s must be > 0" % key

        # check the provided input
        if min_value > value or value > max_value:
            msg = "value for `%s` must be between " % key
            msg += "%s and %s" % (min_value, max_value)
            raise ValueError(msg)

        return value



event.listen(JobModel.state, "set", JobModel.stateChangedEvent)