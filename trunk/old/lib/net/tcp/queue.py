# No shebang line, this module is meant to be imported
#
# INITIAL: April 7 2008 (revised in July 2010)
# PURPOSE: Group of classes dedicated to the collection and monitoring
#          of queue information.
#
# This file is part of PyFarm.
# Copyright (C) 2008-2011 Oliver Palmer
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

import os
import sys
import site

cwd = os.path.dirname(os.path.abspath(__file__))
root = os.path.abspath(os.path.join(cwd, "..", "..", ".."))
site.addsitedir(root)

import xmlrpc
from lib import logger

logger = logger.Logger()

class Resource(xmlrpc.BaseResource):
    def __init__(self, sql, parent):
        super(Resource, self).__init__(sql, parent)

    def ping(self, address):
        '''Try to send data to the remote client, return True on success'''
        logger.notimplemented("Ping not yet implemented")
        return False