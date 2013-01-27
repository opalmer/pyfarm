# No shebang line, this module is meant to be imported
#
# This file is part of PyFarm.
# Copyright (C) 2008-2013 Oliver Palmer
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
#

'''
Internal yaml module meant to wrap around :py:func:`yaml.load`
and :py:func:`yaml.dump`
'''

from __future__ import with_statement

import os
import tempfile
from StringIO import StringIO

from pyfarm.logger import Logger
logger = Logger(__name__)

# get the fastest loader
try:
    from yaml import CLoader as Loader
except ImportError:
    from yaml import Loader

# get the fastest dumper
try:
    from yaml import CDumper as Dumper
except ImportError:
    from yaml import Dumper

# get the underlying functions we'll use for loading
# and dumping
from yaml import load as _load, dump as _dump

def load(stream):
    '''
    Loads data from the provided file stream, stream like object, or file
    path.

    :type stream: str or :py:class:`StringIO.StringIO` or file
    :param stream:
        The object or path to load data from
    '''
    if isinstance(stream, basestring) and os.path.isfile(stream):
        logger.info("loading yaml from %s" % stream)
        stream = open(stream, 'r')

    elif not isinstance(stream, file) and not isinstance(stream, StringIO):
        msg = "Expected stream to be a file path, file object, or"
        msg += "StringIO instance.  Got %s instead" % type(stream)
        raise TypeError(msg)

    else:
        logger.info("loading yaml from %s" % stream)

    # load and return data from the stream and
    # be sure the close the stream afterwards
    try:
        return _load(stream, Loader=Loader)

    finally:
        if hasattr(stream, 'close') and not stream.closed:
            stream.close()
# end load

def dump():
    pass
# end dump


if __name__ == '__main__':
    pass
