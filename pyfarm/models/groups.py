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

from pyfarm.flaskapp import db
from pyfarm.models.constants import TABLE_SOFTWARE, TABLE_AGENT
from pyfarm.models.mixins import RandIdMixin


class Software(db.Model):
    """Generic table which can be used to define group relationships"""
    __tablename__ = TABLE_SOFTWARE
    _agentid = db.Column(db.Integer, db.ForeignKey("%s.id" % TABLE_AGENT))
    name = db.Column(db.String, unique=True, index=True, nullable=False)