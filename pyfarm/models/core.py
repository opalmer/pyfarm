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
"""

from textwrap import dedent

from pyfarm.ext.config.core.loader import Loader
from pyfarm.flaskapp import db

DBCFG = Loader("dbdata.yml")

TABLE_PREFIX = DBCFG.get("tables.prefix")
TABLE_AGENT = "%sagent" % TABLE_PREFIX
TABLE_AGENT_TAGS = "%s_tags" % TABLE_AGENT
TABLE_AGENT_SOFTWARE = "%s_software" % TABLE_AGENT
TABLE_JOB = "%sjob" % TABLE_PREFIX
TABLE_JOB_TAGS = "%s_tags" % TABLE_JOB
TABLE_JOB_SOFTWARE = "%s_software" % TABLE_JOB
TABLE_TASK = "%stask" % TABLE_PREFIX


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
    return db.Column(
        db.Integer, autoincrement=True, primary_key=True, unique=True,
        doc=dedent("""
        Provides an id for the current row.  This value should never be
        directly relied upon and is intended for use in relationships.
        """))