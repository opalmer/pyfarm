# No shebang line, this module is meant to be imported
#
# INITIAL: June 19 2011
# PURPOSE: To create an operate the logger
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

from config import ReadConfig

class Logger(object):
    '''
    Read the logger configration and create an object to handle calls
    to individual log levels.
    '''
    def __init__(self):
        self.config = ReadConfig()