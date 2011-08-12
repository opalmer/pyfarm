# No shebang line, this module is meant to be imported
#
# INITIAL: Aug 12 2011
# PURPOSE: To inspect and return frame information
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
import string


# TODO: Test py2exe frozen modules
if hasattr(sys, 'frozen'): # py2exe frozen module
    _srcfile = "logger%s__init__%s" % (os.sep, __file__[-4:])

# in case we are running from a pyc or pyo file
elif string.lower(__file__[-4:]) in ['.pyc', '.pyo']:
    _srcfile = __file__[:-4] + '.py'

else:
    _srcfile = __file__

# normalize filename
_srcfile = os.path.normcase(_srcfile)

class __Frame(object):
    @property
    def currentFrame(self): #NOTE: This used to be TOP LEVEL
        try:
            raise Exception
        except:
            return sys.exc_traceback.tb_frame.f_back
    # END currentFrame

    @property
    def caller(self):
        '''
        Return a 3-tuple of the caller frame:
            (sourceFile, linenum, methodCall)
        '''
        return ()
    # END caller
# END __Frame

Frame = __Frame()