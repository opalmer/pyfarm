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


"""
Contains core functions and data for use by :mod:`pyfarm.models`

.. include:: ../include/references.rst
"""

from uuid import uuid4
from datetime import datetime
from textwrap import dedent
from pyfarm.flaskapp import db
from pyfarm.ext.config.enum import WorkState
from pyfarm.models.core.types import GUID
from pyfarm.models.core.cfg import DBCFG


def modelfor(model, table):
    """
    Returns True if the given `model` object is for the
    expected `table`.

    >>> from pyfarm.models.constants import TABLE_AGENT
    >>> from pyfarm.models import Agent
    >>> modelfor(Agent("foo", "10.56.0.0", "255.0.0.0"), TABLE_AGENT)
    True
    """
    try:
        return model.__tablename__ == table
    except AttributeError:
        return False


def IDColumn():
    """
    Produces a column used for `id` on each table.  Typically this is done
    using a class in :mod:`pyfarm.models.mixins` however because of the ORM
    and the table relationships it's cleaner to have a function produce
    the column.
    """
    return db.Column(GUID, primary_key=True, unique=True, default=uuid4,
                     doc=dedent("""
                     Provides an id for the current row.  This value should
                     never be directly relied upon and it's intended for use
                     by relationships."""))


def WorkColumns(state_default, priority_default):
    """
    Produces some default columns which are used by models which produce
    work.  Currently this includes |JobModel| and |TaskModel|
    """
    return (
        # id
        IDColumn(),

        # state
        db.Column(db.Integer, default=state_default,
                  doc=dedent("""
                  The state of the job with a value provided by
                  :class:`.WorkState`""")),

        # priority
        db.Column(db.Integer, default=DBCFG.get(priority_default),
                  doc=dedent("""
                  The priority of the job relative to others in the
                  queue.  This is not the same as task priority.

                  **configured by**: `%s`""" % priority_default)),

        # time_submitted
        db.Column(db.DateTime, default=datetime.now,
                               doc=dedent("""
                               The time the job was submitted.  By default this
                               defaults to using :meth:`datetime.datetime.now`
                               as the source of submission time.  This value
                               will not be set more than once and will not
                               change even after a job is requeued.""")),

        # time_started
        db.Column(db.DateTime,
                  doc=dedent("""
                  The time this job was started.  By default this value is set
                  when :attr:`state` is changed to an appropriate value or
                  when a job is requeued.""")),

        # time_finished
        db.Column(db.DateTime,
                  doc=dedent("""
                  Time the job was finished.  This will be set when the last
                  task finishes and reset if a job is requeued."""))
    )