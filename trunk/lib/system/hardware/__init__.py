'''
HOMEPAGE: www.pyfarm.net
PURPOSE: To import the standard includes and setup the package

This file is part of PyFarm.
Copyright (C) 2008-2011 Oliver Palmer

PyFarm is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

PyFarm is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with PyFarm.  If not, see <http://www.gnu.org/licenses/>.
'''
import os
import platform

if os.name == 'nt':
    from windows import *

elif os.name == 'posix' and "CYGWIN" not in platform.platform():
    from linux import *

elif os.name == 'posix' and "CYGWIN" in platform.platform():
    from cygwin import *

elif os.name == 'mac':
    from macosx import *

else:
    raise Exception("%s is not a supported system!" % os.name)
