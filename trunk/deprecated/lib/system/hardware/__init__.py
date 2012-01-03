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

import os
import site
import platform

# setup root path
cwd = os.path.abspath(os.path.dirname(__file__))
root = os.path.abspath(os.path.join(cwd, "..", "..", ".."))
site.addsitedir(root)

# cleanup variables
del site, cwd, root

# import includes
try: from includes import *
except ImportError: pass

# import errors
try: from errors import *
except ImportError: pass

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

del os, platform