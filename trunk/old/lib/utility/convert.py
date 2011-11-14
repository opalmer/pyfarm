# No shebang line, this module is meant to be imported
#
# INITIAL: June 16 2011
# PURPOSE: To convert various forms of information between formats or types
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

def kBToMB(kB):
    '''bilobytes to megabytes'''
    return kB / 1024

def kbToMB(kb):
    '''kilobits to megabytes'''
    return kb / 1024 / 1024