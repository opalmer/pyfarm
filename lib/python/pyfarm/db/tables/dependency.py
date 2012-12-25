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

'''
storage class for frame-to-frame, frame-job and job-job
dependencies
'''

from sqlalchemy import Column, ForeignKey
from sqlalchemy.types import Integer
from sqlalchemy.orm import validates

from pyfarm.db.tables import Base
from pyfarm.db.tables._constants import TABLE_DEPENDENCIES_F2F, \
    TABLE_DEPENDENCIES_J2J, TABLE_FRAME, TABLE_JOB


class DependencyF2F(Base):
    __tablename__ = TABLE_DEPENDENCIES_F2F
# end DependencyF2F


class DependencyJ2J(Base):
    __tablename__ = TABLE_DEPENDENCIES_J2J
# end DependencyJ2J

if __name__ == '__main__':
    pass
