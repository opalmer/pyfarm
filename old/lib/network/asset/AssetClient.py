'''
HOMEPAGE: www.pyfarm.net
INITIAL: October 14 2009
PURPOSE: Network modules used in the administration of remote clients

    This file is part of PyFarm.
    Copyright (C) 2008-2010 Oliver Palmer

    PyFarm is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    PyFarm is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''

# From Python
import os
import os.path

# From PyQt
from PyQt4.QtCore import QThread

from lib.Logger import Logger
"""
NOTES:
Options 1:
    -Tar or zip the directory, send the link
Option 2:
    -Send ALL files links

"""
#urllib.urlretrieve(url[, filename[, reporthook[, data]]])