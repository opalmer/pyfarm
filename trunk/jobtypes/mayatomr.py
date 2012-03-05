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

from common import logger, datatypes

import base

class Job(base.Job):
    def __init__(self, jobid, frame):
        # Server -> Client: pickup job and frame
        # - client adds frame to db if it does not exist, updates
        #   it if it does
        # - job information stored in data is retrieved and used to construct
        #   base job


#        super(Job, self).__init__(self, command,)
# end Job
