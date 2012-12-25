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
from sqlalchemy.orm import validates, relationship

from pyfarm.db.tables import Base
from pyfarm.db.tables._constants import TABLE_DEPENDENCIES_F2F, \
    TABLE_DEPENDENCIES_J2J, TABLE_FRAME, TABLE_JOB

__all__ = ['F2FDependency', 'J2JDependency']


class F2FDependency(Base):
    '''defines frame to frame dependencies'''
    __tablename__ = TABLE_DEPENDENCIES_F2F
    repr_attrs = ("_parent", "_child")

    # column definitions
    _parent = Column(Integer, ForeignKey('%s.id' % TABLE_FRAME))
    _child = Column(Integer, ForeignKey('%s.id' % TABLE_FRAME))

    # relationship definitions
    parent = relationship(
        'Frame',
        primaryjoin='(Frame.id == F2FDependency._parent)',
        backref='ref_dependencyf2f_parent'
    )
    child = relationship(
        'Frame',
        primaryjoin='(Frame.id == F2FDependency._child)',
        backref='ref_dependencyf2f_child'
    )

    def __init__(self, parent, child):
        self._parent = parent.id if hasattr(parent, 'id') else parent
        self._child = child.id if hasattr(child, 'id') else child

        if self._parent == self._child:
            raise ValueError("a frame cannot be dependent on itself")
    # end __init__
# end F2FDependency


class J2JDependency(Base):
    '''defines job to job dependencies'''
    __tablename__ = TABLE_DEPENDENCIES_J2J
    repr_attrs = ("_parent", "_child")

    # column definitions
    _parent = Column(Integer, ForeignKey('%s.id' % TABLE_JOB))
    _child = Column(Integer, ForeignKey('%s.id' % TABLE_JOB))

    # relationship definitions
    parent = relationship(
        'Job',
        primaryjoin='(Job.id == J2JDependency._parent)',
        backref='ref_dependencyj2j_parent'
    )
    child = relationship(
        'Job',
        primaryjoin='(Job.id == J2JDependency._child)',
        backref='ref_dependencyj2j_child'
    )

    def __init__(self, parent, child):
        self._parent = parent.id if hasattr(parent, 'id') else parent
        self._child = child.id if hasattr(child, 'id') else child

        if self._parent == self._child:
            raise ValueError("a job cannot be dependent on itself")
    # end __init__
# end J2JDependency

if __name__ == '__main__':
    pass
